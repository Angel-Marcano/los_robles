<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Invoice;
use App\Models\Ownership;
use App\Models\User;

class GetInvoiceBalanceTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_invoice_balance';
    }

    public function description(): string
    {
        return 'Consulta el saldo pendiente, mora y última factura de un apartamento del usuario.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'apartment_id' => [
                    'type' => 'integer',
                    'description' => 'ID del apartamento. Si no se indica, usa el apartamento por defecto del usuario.',
                ],
            ],
        ];
    }

    public function execute(User $user, array $args, array $context): ToolResult
    {
        $apartmentId = $args['apartment_id'] ?? ($context['default_apartment_id'] ?? null);

        if (!$apartmentId) {
            return ToolResult::error('No tienes apartamentos asociados para consultar saldo.');
        }

        // Verificar que el apartamento pertenezca al usuario
        $owns = Ownership::where('user_id', $user->id)
            ->where('apartment_id', $apartmentId)
            ->where('active', true)
            ->exists();

        if (!$owns && empty($context['is_admin'])) {
            return ToolResult::error('No tienes permiso para consultar ese apartamento.');
        }

        $invoice = Invoice::where('apartment_id', $apartmentId)
            ->whereNull('voided_at')
            ->orderByDesc('due_date')
            ->first();

        if (!$invoice) {
            return ToolResult::ok('No tienes facturas pendientes para ese apartamento.');
        }

        $lateUsd = $invoice->computeLateFeeUsd();
        $totalUsd = (float) $invoice->total_usd;
        $grandUsd = $totalUsd + $lateUsd;
        $approved = $invoice->approvedPaidUsdEquivalent();
        $remaining = max(0.0, round($grandUsd - $approved, 2));

        return ToolResult::ok(
            "Factura {$invoice->number} (vence {$invoice->due_date->format('d/m/Y')}): total USD " .
            number_format($totalUsd, 2) .
            ", mora USD " . number_format($lateUsd, 2) .
            ", saldo pendiente USD " . number_format($remaining, 2) . ".",
            [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'total_usd' => $totalUsd,
                'late_fee_usd' => $lateUsd,
                'remaining_usd' => $remaining,
                'due_date' => $invoice->due_date->toDateString(),
            ]
        );
    }
}
