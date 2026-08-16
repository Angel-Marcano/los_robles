<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HumanHandoffService
{
    /**
     * Detecta si el mensaje solicita escalamiento a humano.
     */
    public function isHandoffRequest(string $message): bool
    {
        $lower = mb_strtolower($message);
        $triggers = [
            'administrador', 'humano', 'persona', 'hablar con', 'quiero hablar',
            'reclamo', 'queja formal', 'abogado', 'demanda', 'no me ayudas',
            'no entiendes', 'no resuelves', 'soporte humano',
        ];

        foreach ($triggers as $trigger) {
            if (str_contains($lower, $trigger)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Marca conversación para atención humana y notifica.
     */
    public function escalate(ChatbotConversation $conversation, User $user): void
    {
        $conversation->update(['needs_human' => true]);

        Log::info('Chatbot escalated to human', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        // Notificar a admins del condominio (placeholder; ajustar según lógica de notificación)
        try {
            $admins = User::role(['super_admin', 'condo_admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->email) {
                    Mail::to($admin->email)
                        ->queue(new \App\Mail\ChatbotHandoffMail($conversation, $user));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send handoff email', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
