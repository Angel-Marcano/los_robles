<?php

namespace App\Services\Chatbot\Tools;

use App\Models\CurrencyRate;
use App\Models\Invoice;
use App\Models\Ownership;
use App\Models\PaymentReport;
use App\Models\User;

class ReportPaymentTool implements ToolInterface
{
    public function name(): string
    {
        return 'report_payment';
    }

    public function description(): string
    {
        return 'Crea un reporte de pago para una factura del usuario. Requiere confirmación explícita.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'invoice_id' => ['type' => 'integer', 'description' => 'ID de la factura a pagar.'],
                'amount_usd' => ['type' => 'number', 'description' => 'Monto pagado en USD.'],
                'amount_ves' => ['type' => 'number', 'description' => 'Monto pagado en VES.'],
                'payment_method' => ['type' => 'string', 'description' => 'Método de pago: transferencia, zelle, efectivo, etc.'],
                'reference' => ['type' => 'string', 'description' => 'Número de referencia del pago.'],
                'paid_at' => ['type' => 'string', 'description' => 'Fecha de pago (Y-m-d).'],
            ],
            'required' => ['invoice_id'],
        ];
    }

    public function execute(User $user, array $args, array $context): ToolResult
    {
        $invoiceId = $args['invoice_id'] ?? null;
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return ToolResult::error('No encontré la factura indicada.');
        }

        // Verificar ownership
        $owns = Ownership::where('user_id', $user->id)
            ->where('apartment_id', $invoice->apartment_id)
            ->where('active', true)
            ->exists();

        if (!$owns && empty($context['is_admin'])) {
            return ToolResult::error('No tienes permiso para reportar pagos de esa factura.');
        }

        if ($invoice->voided_at) {
            return ToolResult::error('La factura está anulada, no se puede reportar pago.');
        }

        // Si faltan datos, pedirlos
        $missing = [];
        if (empty($args['amount_usd']) && empty($args['amount_ves'])) {
            $missing[] = 'monto pagado (USD o VES)';
        }
        if (empty($args['payment_method'])) {
            $missing[] = 'método de pago';
        }
        if (empty($args['reference'])) {
            $missing[] = 'número de referencia';
        }
        if (empty($args['paid_at'])) {
            $missing[] = 'fecha de pago';
        }

        if (!empty($missing)) {
            return ToolResult::needsInfo('Para reportar el pago necesito: ' . implode(', ', $missing) . '.');
        }

        $amountUsd = (float) ($args['amount_usd'] ?? 0);
        $amountVes = (float) ($args['amount_ves'] ?? 0);
        $rate = CurrencyRate::where('active', true)->orderByDesc('valid_from')->first();
        $exchangeRateUsed = $rate ? (float) $rate->rate : (float) $invoice->exchange_rate_used;

        $summary = "Reportar pago de USD " . number_format($amountUsd, 2) .
            " / VES " . number_format($amountVes, 2) .
            " para la factura {$invoice->number} mediante {$args['payment_method']} (ref: {$args['reference']}).";

        return ToolResult::needsConfirmation(
            "¿Confirmas {$summary}? Responde 'sí' para confirmar o 'no' para cancelar.",
            [
                'tool' => 'report_payment',
                'invoice_id' => $invoice->id,
                'apartment_id' => $invoice->apartment_id,
                'amount_usd' => $amountUsd,
                'amount_ves' => $amountVes,
                'payment_method' => $args['payment_method'],
                'reference' => $args['reference'],
                'paid_at' => $args['paid_at'],
                'exchange_rate_used' => $exchangeRateUsed,
                'currency_rate_id' => $rate ? $rate->id : null,
                'exchange_rate_valid_from' => $rate ? $rate->valid_from : null,
            ]
        );
    }

    /**
     * Ejecuta la creación real del PaymentReport tras confirmación.
     */
    public function confirm(User $user, array $data): ToolResult
    {
        $report = PaymentReport::create([
            'invoice_id' => $data['invoice_id'],
            'apartment_id' => $data['apartment_id'],
            'user_id' => $data['user_id'] ?? $user->id,
            'reported_by' => $user->id,
            'amount_usd' => $data['amount_usd'],
            'amount_ves' => $data['amount_ves'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference'],
            'paid_at' => $data['paid_at'],
            'exchange_rate_used' => $data['exchange_rate_used'],
            'currency_rate_id' => $data['currency_rate_id'] ?? null,
            'exchange_rate_valid_from' => $data['exchange_rate_valid_from'] ?? null,
            'status' => 'reported',
        ]);

        return ToolResult::ok(
            "Pago reportado correctamente con número #{$report->id}. Queda pendiente de aprobación por administración.",
            ['payment_report_id' => $report->id]
        );
    }
}
