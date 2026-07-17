<?php

namespace App\Services\Chatbot\Tools;

use App\Models\SupportTicket;
use App\Models\User;

class CreateSupportTicketTool implements ToolInterface
{
    public function name(): string
    {
        return 'create_support_ticket';
    }

    public function description(): string
    {
        return 'Crea un ticket de soporte para el usuario. Requiere confirmación explícita.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'description' => 'Categoría: billing, maintenance, security, general.',
                ],
                'priority' => [
                    'type' => 'string',
                    'description' => 'Prioridad: low, medium, high.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Descripción detallada del problema o solicitud.',
                ],
            ],
            'required' => ['description'],
        ];
    }

    public function execute(User $user, array $args, array $context): ToolResult
    {
        $description = trim($args['description'] ?? '');
        if (empty($description)) {
            return ToolResult::needsInfo('Por favor describe el problema o solicitud para crear el ticket.');
        }

        $category = $this->normalizeCategory($args['category'] ?? 'general');
        $priority = $this->normalizePriority($args['priority'] ?? 'medium');

        return ToolResult::needsConfirmation(
            "¿Confirmas crear un ticket de soporte de categoría '{$category}' y prioridad '{$priority}'? Responde 'sí' para confirmar o 'no' para cancelar.",
            [
                'tool' => 'create_support_ticket',
                'user_id' => $user->id,
                'condominium_id' => $context['condominium_id'] ?? null,
                'category' => $category,
                'priority' => $priority,
                'description' => $description,
            ]
        );
    }

    public function confirm(User $user, array $data): ToolResult
    {
        $ticket = SupportTicket::create([
            'user_id' => $data['user_id'],
            'condominium_id' => $data['condominium_id'] ?? null,
            'category' => $data['category'],
            'priority' => $data['priority'],
            'description' => $data['description'],
            'status' => 'open',
        ]);

        return ToolResult::ok(
            "Ticket de soporte creado correctamente con número #{$ticket->id}. Te contactaremos pronto.",
            ['support_ticket_id' => $ticket->id]
        );
    }

    protected function normalizeCategory(string $category): string
    {
        $map = [
            'facturacion' => 'billing',
            'facturación' => 'billing',
            'pago' => 'billing',
            'mantenimiento' => 'maintenance',
            'seguridad' => 'security',
            'soporte' => 'general',
            'general' => 'general',
        ];

        return $map[mb_strtolower($category)] ?? 'general';
    }

    protected function normalizePriority(string $priority): string
    {
        $map = [
            'baja' => 'low',
            'media' => 'medium',
            'alta' => 'high',
            'low' => 'low',
            'medium' => 'medium',
            'high' => 'high',
        ];

        return $map[mb_strtolower($priority)] ?? 'medium';
    }
}
