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
     * Acredita a los fondos de reserva (torre y/o general) la porción de reserva cobrada
     * cuando una factura se marca como pagada. El aporte se registra en la moneda realmente
     * pagada (proporcional a lo cobrado en USD y VES). Fondos aislados por torre + un fondo
     * general del condominio.
     *
     * @return ReserveFundMovement|null El último movimiento creado (o null si no hubo).
     */
    public function creditFromPaidInvoice(Invoice $invoice): ?ReserveFundMovement
    {
        $reserveItems = $invoice->items()->where('is_reserve', true)->get();
        if ($reserveItems->isEmpty()) { return null; }

        $approved = $invoice->paymentReports()->where('status', 'approved')->get();
        $paidUsd  = (float) $approved->sum('amount_usd');
        $paidVes  = (float) $approved->sum('amount_ves');
        $rate = (float) ($invoice->paid_exchange_rate ?? $invoice->exchange_rate_used ?? 0);
        $aptCode = optional($invoice->apartment)->code;
        $invoiceUsdEq = (float) $invoice->dueUsdEquivalent();

        $lastMovement = null;

        // Separar por tipo de reserva
        foreach (['tower', 'general'] as $type) {
            $items = $reserveItems->where('reserve_type', $type);
            if ($items->isEmpty()) { continue; }

            // Evitar doble crédito por tipo
            $already = ReserveFundMovement::where('invoice_id', $invoice->id)
                ->where('source', 'invoice')
                ->where('direction', 'income')
                ->where('reserve_type', $type)
                ->exists();
            if ($already) { continue; }

            $reserveUsd = round((float) $items->sum('subtotal_usd'), 2);
            $reserveVes = round((float) $items->sum('subtotal_ves'), 2);
            if ($reserveUsd <= 0 && $reserveVes <= 0) { continue; }

            // Resolver el fondo destino
            $fund = null;
            if ($type === 'tower') {
                $towerId = $invoice->tower_id ?? optional($invoice->apartment)->tower_id;
                if (!$towerId) { continue; }
                $tower = Tower::find($towerId);
                if (!$tower) { continue; }
                $fund = ReserveFund::forTower($tower);
            } else { // general
                $condo = app()->bound('currentCondominium') ? app('currentCondominium') : null;
                if (!$condo) { continue; }
                $fund = ReserveFund::forCondominium($condo);
            }

            // Reparto proporcional a lo pagado
            $fraction = $invoiceUsdEq > 0 ? ($reserveUsd / $invoiceUsdEq) : 0;
            $creditUsd = round($paidUsd * $fraction, 2);
            $creditVes = round($paidVes * $fraction, 2);

            // Fallback: montos nominales si no se pudo prorratear
            if ($creditUsd <= 0 && $creditVes <= 0) {
                $creditUsd = $reserveUsd;
                $creditVes = 0;
            }

            $label = $type === 'general' ? 'general del condominio' : 'de torre';
            $notes = 'Aporte de fondo de reserva '.$label.' por factura '.$invoice->number
                .($aptCode ? ' (apto '.$aptCode.')' : '')
                .'. Reserva facturada: '.number_format($reserveUsd, 2).' USD'
                .($rate > 0 ? '. Tasa de pago: '.rtrim(rtrim(number_format($rate, 6, '.', ''), '0'), '.') : '')
                .'.';

            $lastMovement = $this->registerMovement($fund, 'income', [
                'source'        => 'invoice',
                'reserve_type'  => $type,
                'invoice_id'    => $invoice->id,
                'apartment_id'  => $invoice->apartment_id,
                'amount_usd'    => $creditUsd,
                'amount_ves'    => $creditVes,
                'exchange_rate' => $rate > 0 ? $rate : null,
                'notes'         => $notes,
            ]);
        }

        return $lastMovement;
    }
}
