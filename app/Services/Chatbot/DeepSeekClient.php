<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Log;
use OpenAI;
use OpenAI\Client;
use Throwable;

class DeepSeekClient
{
    protected Client $client;
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?: config('services.deepseek', []);

        $apiKey = $this->config['api_key'] ?? null;
        $baseUrl = $this->config['base_url'] ?? 'https://api.deepseek.com';

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('DEEPSEEK_API_KEY no está configurada.');
        }

        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => $this->config['timeout'] ?? 30]))
            ->make();
    }

    /**
     * Envía mensajes a DeepSeek y devuelve respuesta estructurada.
     *
     * @param array $messages Formato OpenAI: [['role'=>'system'|'user'|'assistant', 'content'=>'...'], ...]
     * @param array|null $tools Definición de tools para function calling (opcional)
     * @param string|null $model Forzar modelo específico
     * @return array ['content' => string|null, 'tool_calls' => array, 'usage' => array, 'model' => string]
     */
    public function chat(array $messages, ?array $tools = null, ?string $model = null): array
    {
        $model = $model ?: ($this->config['model'] ?? 'deepseek-chat');
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $this->config['temperature'] ?? 0.3,
            'max_tokens' => $this->config['max_tokens'] ?? 1024,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $start = microtime(true);
        try {
            $response = $this->client->chat()->create($payload);
        } catch (Throwable $e) {
            Log::error('DeepSeek API error', [
                'model' => $model,
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            throw new \RuntimeException('Error al comunicarse con DeepSeek: ' . $e->getMessage(), 0, $e);
        }
        $durationMs = round((microtime(true) - $start) * 1000, 2);

        $message = $response->choices[0]->message ?? null;
        $content = $message->content ?? null;
        $toolCalls = [];
        if ($message && !empty($message->toolCalls)) {
            foreach ($message->toolCalls as $tc) {
                $toolCalls[] = [
                    'id' => $tc->id,
                    'type' => $tc->type,
                    'function' => [
                        'name' => $tc->function->name,
                        'arguments' => $tc->function->arguments,
                    ],
                ];
            }
        }

        $usage = [
            'prompt_tokens' => $response->usage->promptTokens ?? 0,
            'completion_tokens' => $response->usage->completionTokens ?? 0,
            'total_tokens' => $response->usage->totalTokens ?? 0,
        ];

        Log::info('DeepSeek API call', [
            'model' => $model,
            'duration_ms' => $durationMs,
            'usage' => $usage,
        ]);

        return [
            'content' => $content,
            'tool_calls' => $toolCalls,
            'usage' => $usage,
            'model' => $model,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * Envía un ping simple para validar conectividad.
     */
    public function ping(): string
    {
        $response = $this->chat([
            ['role' => 'system', 'content' => 'Responde únicamente "Pong desde DeepSeek".'],
            ['role' => 'user', 'content' => 'ping'],
        ]);

        return trim($response['content'] ?? 'Sin respuesta');
    }
}
