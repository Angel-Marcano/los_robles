<?php
namespace App\Services;

use App\Models\{Invoice, Tower, ReserveFund, ReserveFundMovement};
use Illuminate\Support\Facades\DB;

class ReserveFundService
{
    /**
     * Registra un movimiento en el fondo y actualiza su saldo (USD y/o VES).
     * income suma al saldo; expense lo resta.
     */
    public function registerMovement(ReserveFund $fund, string $direction, array $data): ReserveFundMovement
    {
        return DB::transaction(function () use ($fund, $direction, $data) {
            $usd  = round((float) ($data['amount_usd'] ?? 0), 2);
            $ves  = round((float) ($data['amount_ves'] ?? 0), 2);
            $sign = $direction === 'income' ? 1 : -1;

            if ($usd != 0) { $fund->increment('balance_usd', $sign * $usd); }
            if ($ves != 0) { $fund->increment('balance_ves', $sign * $ves); }

            return $fund->movements()->create([
                'direction'     => $direction,
                'source'        => $data['source'] ?? 'manual',
                'invoice_id'    => $data['invoice_id'] ?? null,
                'apartment_id'  => $data['apartment_id'] ?? null,
                'amount_usd'    => $usd,
                'amount_ves'    => $ves,
                'exchange_rate' => $data['exchange_rate'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'user_id'       => auth()->id(),
            ]);
        });
    }

    /**
     * Acredita al fondo de la torre la porción de reserva cobrada cuando una factura
     * se marca como pagada. El aporte se registra en la moneda realmente pagada
     * (proporcional a lo cobrado en USD y VES). Fondos aislados por torre.
     */
    public function creditFromPaidInvoice(Invoice $invoice): ?ReserveFundMovement
    {
        // Evitar doble crédito.
        $already = ReserveFundMovement::where('invoice_id', $invoice->id)
            ->where('source', 'invoice')
            ->where('direction', 'income')
            ->exists();
        if ($already) { return null; }

        $reserveItems = $invoice->items()->where('is_reserve', true)->get();
        if ($reserveItems->isEmpty()) { return null; }

        $reserveUsd = round((float) $reserveItems->sum('subtotal_usd'), 2);
        $reserveVes = round((float) $reserveItems->sum('subtotal_ves'), 2);
        if ($reserveUsd <= 0 && $reserveVes <= 0) { return null; }

        $towerId = $invoice->tower_id ?? optional($invoice->apartment)->tower_id;
        if (!$towerId) { return null; }
        $tower = Tower::find($towerId);
        if (!$tower) { return null; }
        $fund = ReserveFund::forTower($tower);

        // Reparto por moneda realmente pagada (proporcional a la fracción de reserva de la factura).
        $invoiceUsdEq = (float) $invoice->dueUsdEquivalent();
        $fraction = $invoiceUsdEq > 0 ? ($reserveUsd / $invoiceUsdEq) : 0;

        $approved = $invoice->paymentReports()->where('status', 'approved')->get();
        $paidUsd  = (float) $approved->sum('amount_usd');
        $paidVes  = (float) $approved->sum('amount_ves');

        $creditUsd = round($paidUsd * $fraction, 2);
        $creditVes = round($paidVes * $fraction, 2);

        // Fallback: si no se pudo prorratear (datos incompletos), usar los montos nominales de la reserva.
        if ($creditUsd <= 0 && $creditVes <= 0) {
            $creditUsd = $reserveUsd;
            $creditVes = 0;
        }

        $rate = (float) ($invoice->paid_exchange_rate ?? $invoice->exchange_rate_used ?? 0);
        $aptCode = optional($invoice->apartment)->code;
        $notes = 'Aporte de fondo de reserva por factura '.$invoice->number
            .($aptCode ? ' (apto '.$aptCode.')' : '')
            .'. Reserva facturada: '.number_format($reserveUsd, 2).' USD'
            .($rate > 0 ? '. Tasa de pago: '.rtrim(rtrim(number_format($rate, 6, '.', ''), '0'), '.') : '')
            .'.';

        return $this->registerMovement($fund, 'income', [
            'source'        => 'invoice',
            'invoice_id'    => $invoice->id,
            'apartment_id'  => $invoice->apartment_id,
            'amount_usd'    => $creditUsd,
            'amount_ves'    => $creditVes,
            'exchange_rate' => $rate > 0 ? $rate : null,
            'notes'         => $notes,
        ]);
    }
}
