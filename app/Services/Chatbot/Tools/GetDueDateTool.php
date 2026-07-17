<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Invoice;
use App\Models\Ownership;
use App\Models\User;

class GetDueDateTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_due_date';
    }

    public function description(): string
    {
        return 'Consulta la fecha de vencimiento de la factura más reciente de un apartamento.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'apartment_id' => [
                    'type' => 'integer',
                    'description' => 'ID del apartamento. Si no se indica, usa el apartamento por defecto.',
                ],
            ],
        ];
    }

    public function execute(User $user, array $args, array $context): ToolResult
    {
        $apartmentId = $args['apartment_id'] ?? ($context['default_apartment_id'] ?? null);

        if (!$apartmentId) {
            return ToolResult::error('No tienes apartamentos asociados.');
        }

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
            return ToolResult::ok('No tienes facturas registradas para ese apartamento.');
        }

        return ToolResult::ok(
            "La factura {$invoice->number} vence el {$invoice->due_date->format('d/m/Y')}.",
            ['invoice_id' => $invoice->id, 'due_date' => $invoice->due_date->toDateString()]
        );
    }
}
