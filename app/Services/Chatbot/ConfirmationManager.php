<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\User;

class ConfirmationManager
{
    /**
     * Guarda una acción pendiente de confirmación en la última conversación del usuario.
     */
    public function storePendingAction(User $user, string $sessionId, array $actionData): ChatbotConversation
    {
        return ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'channel' => 'web',
            'intent' => $actionData['tool'] ?? 'unknown',
            'is_action_pending' => true,
            'pending_action' => $actionData,
            'pending_action_expires_at' => now()->addMinutes(5),
            'input_raw' => null,
            'input_sanitized' => null,
            'output_raw' => null,
            'output_sanitized' => null,
        ]);
    }

    /**
     * Recupera la acción pendiente vigente del usuario en la sesión.
     */
    public function getPendingAction(User $user, string $sessionId): ?array
    {
        $row = ChatbotConversation::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->where('is_action_pending', true)
            ->where('pending_action_expires_at', '>', now())
            ->orderByDesc('created_at')
            ->first();

        return $row ? $row->pending_action : null;
    }

    /**
     * Marca la acción pendiente como resuelta.
     */
    public function resolvePendingAction(User $user, string $sessionId): void
    {
        ChatbotConversation::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->where('is_action_pending', true)
            ->update([
                'is_action_pending' => false,
                'pending_action_expires_at' => now(),
            ]);
    }

    /**
     * Determina si un mensaje es confirmación positiva, negativa o neutro.
     */
    public function parseConfirmation(string $message): string
    {
        $lower = mb_strtolower(trim($message));
        $positive = ['sí', 'si', 'confirmo', 'confirmar', 'ok', 'vale', 'correcto', 's', 'yes', 'y'];
        $negative = ['no', 'cancelar', 'nope', 'negativo', 'n'];

        foreach ($positive as $word) {
            if ($lower === $word || str_contains($lower, $word)) {
                return 'confirmed';
            }
        }

        foreach ($negative as $word) {
            if ($lower === $word || str_contains($lower, $word)) {
                return 'rejected';
            }
        }

        return 'unknown';
    }
}
