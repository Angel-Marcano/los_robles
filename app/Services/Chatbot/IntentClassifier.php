<?php

namespace App\Services\Chatbot;

class IntentClassifier
{
    protected DeepSeekClient $client;

    public function __construct(DeepSeekClient $client)
    {
        $this->client = $client;
    }

    /**
     * Clasifica intención usando reglas rápidas + DeepSeek si no hay match.
     *
     * @param array $history Mensajes previos en formato OpenAI para contexto.
     */
    public function classify(string $message, array $history = []): array
    {
        $lower = mb_strtolower($message);

        // Reglas rápidas primero
        $ruleIntent = $this->ruleBasedClassify($lower);
        if ($ruleIntent) {
            return [
                'intent' => $ruleIntent,
                'confidence' => 'high',
                'entities' => $this->extractEntities($message),
            ];
        }

        // Fallback a DeepSeek con historial reciente
        $sanitized = PiiSanitizer::sanitize($message);
        $messages = [
            ['role' => 'system', 'content' => ChatbotConfig::intentClassificationPrompt()],
        ];
        foreach ($history as $h) {
            $messages[] = $h;
        }
        $messages[] = ['role' => 'user', 'content' => $sanitized];

        $response = $this->client->chat($messages);

        $content = $response['content'] ?? '{}';
        $parsed = json_decode($content, true);

        if (!is_array($parsed) || empty($parsed['intent'])) {
            return [
                'intent' => 'unknown',
                'confidence' => 'low',
                'entities' => $this->extractEntities($message),
            ];
        }

        $intent = in_array($parsed['intent'], ChatbotConfig::allowedIntents(), true)
            ? $parsed['intent']
            : 'unknown';

        return [
            'intent' => $intent,
            'confidence' => $parsed['confidence'] ?? 'medium',
            'entities' => array_merge($this->extractEntities($message), $parsed['entities'] ?? []),
        ];
    }

    protected function ruleBasedClassify(string $lower): ?string
    {
        if (str_contains($lower, 'pago') && (str_contains($lower, 'reportar') || str_contains($lower, 'reporte') || str_contains($lower, 'reporté'))) {
            return 'report_payment';
        }
        if (str_contains($lower, 'ticket') || str_contains($lower, 'soporte') || str_contains($lower, 'reclamo') || str_contains($lower, 'incidencia')) {
            return 'create_ticket';
        }
        if (str_contains($lower, 'cuanto debo') || str_contains($lower, 'saldo') || str_contains($lower, 'deuda') || str_contains($lower, 'balance')) {
            return 'balance';
        }
        if (str_contains($lower, 'vencimiento') || str_contains($lower, 'vence') || str_contains($lower, 'fecha de pago')) {
            return 'due_date';
        }
        if (str_contains($lower, 'estado del pago') || str_contains($lower, 'pago aprobado') || str_contains($lower, 'pago rechazado')) {
            return 'payment_status';
        }
        if (str_contains($lower, 'administrador') || str_contains($lower, 'humano') || str_contains($lower, 'hablar con') || str_contains($lower, 'no entiendo')) {
            return 'human_handoff';
        }

        return null;
    }

    protected function extractEntities(string $message): array
    {
        $entities = [
            'apartment_id' => null,
            'invoice_id' => null,
            'amount_usd' => null,
            'amount_ves' => null,
            'category' => null,
            'description' => null,
        ];

        // Montos USD
        if (preg_match('/(\d+[.,]?\d*)\s*usd/i', $message, $m)) {
            $entities['amount_usd'] = (float) str_replace(',', '.', $m[1]);
        }
        // Montos VES
        if (preg_match('/(\d+[.,]?\d*)\s*(ves|bs|bolivares|bolívares)/i', $message, $m)) {
            $entities['amount_ves'] = (float) str_replace(',', '.', $m[1]);
        }
        // IDs de factura (#123, factura 123)
        if (preg_match('/(?:factura|invoice)\s*#?(\d+)/i', $message, $m)) {
            $entities['invoice_id'] = (int) $m[1];
        }
        // Apartamento (apt 101, apartamento 101)
        if (preg_match('/(?:apartamento|apto|apt)\s*#?(\w+)/i', $message, $m)) {
            $entities['apartment_code'] = $m[1];
        }

        return $entities;
    }
}
