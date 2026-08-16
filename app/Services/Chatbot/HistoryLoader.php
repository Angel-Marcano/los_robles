<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\User;

class HistoryLoader
{
    /**
     * Recupera los últimos mensajes de la sesión para enviar como contexto al LLM.
     * Devuelve array en formato OpenAI: [['role'=>'user'|'assistant', 'content'=>'...'], ...].
     *
     * @param User $user
     * @param string $sessionId
     * @param int|null $limit Si es null, usa CHATBOT_MAX_HISTORY_MESSAGES.
     * @return array
     */
    public function load(User $user, string $sessionId, ?int $limit = null): array
    {
        $limit = $limit ?? ChatbotConfig::maxHistoryMessages();
        $limit = max(1, min($limit, 50)); // hard cap de seguridad

        $rows = ChatbotConversation::forUser($user->id)
            ->forSession($sessionId)
            ->whereNotNull('input_sanitized')
            ->whereNotNull('output_raw')
            ->where('intent', '!=', 'blocked_by_guardrails')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['input_sanitized', 'output_raw', 'created_at']);

        $messages = [];
        // Se recorre en orden inverso para reconstruir cronológicamente
        foreach ($rows->reverse() as $row) {
            $userContent = trim((string) $row->input_sanitized);
            $botContent = trim((string) $row->output_raw);

            if ($userContent !== '') {
                $messages[] = ['role' => 'user', 'content' => $userContent];
            }
            if ($botContent !== '') {
                $messages[] = ['role' => 'assistant', 'content' => $botContent];
            }
        }

        return $messages;
    }
}
