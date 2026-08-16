<?php

namespace App\Services\Chatbot\Tools;

use App\Models\PaymentReport;
use App\Models\Ownership;
use App\Models\User;

class GetPaymentStatusTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_payment_status';
    }

    public function description(): string
    {
        return 'Consulta el estado de los reportes de pago recientes del usuario.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Cantidad máxima de reportes a mostrar.',
                ],
            ],
        ];
    }

    public function execute(User $user, array $args, array $context): ToolResult
    {
        $limit = min((int) ($args['limit'] ?? 5), 10);

        $apartmentIds = Ownership::where('user_id', $user->id)
            ->where('active', true)
            ->pluck('apartment_id')
            ->toArray();

        if (empty($apartmentIds)) {
            return ToolResult::error('No tienes apartamentos asociados.');
        }

        $reports = PaymentReport::query()
            ->whereIn('apartment_id', $apartmentIds)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'status', 'amount_usd', 'amount_ves', 'created_at']);

        if ($reports->isEmpty()) {
            return ToolResult::ok('No tienes reportes de pago registrados.');
        }

        $lines = [];
        foreach ($reports as $r) {
            $lines[] = "Reporte #{$r->id}: {$r->statusLabel()} — USD " .
                number_format((float) $r->amount_usd, 2) .
                " / VES " . number_format((float) $r->amount_ves, 2) .
                " ({$r->created_at->format('d/m/Y')})";
        }

        return ToolResult::ok("Últimos reportes de pago:\n" . implode("\n", $lines), ['count' => $reports->count()]);
    }
}
