<?php

namespace App\Services\Chatbot\Tools;

use App\Models\User;
use Illuminate\Support\Facades\File;

class GetCondoRulesTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_condo_rules';
    }

    public function description(): string
    {
        return 'Busca respuestas en la base de conocimiento del condominio (reglas, horarios, pagos, etc.).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'topic' => [
                    'type' => 'string',
                    'description' => 'Tema sobre el que buscar información: billing, payments, general, support.',
                ],
            ],
        ];
    }

    public function execute(User $user, array $args, array $context): ToolResult
    {
        $topic = $args['topic'] ?? 'general';
        $allowed = ['general', 'billing', 'payments', 'support'];
        $file = in_array($topic, $allowed, true) ? $topic : 'general';
        $path = ChatbotConfig::knowledgeBasePath() . "/{$file}.md";

        if (!File::exists($path)) {
            return ToolResult::error('No se encontró información sobre ese tema.');
        }

        $content = File::get($path);
        // Limitar tamaño para no saturar contexto
        $content = mb_substr($content, 0, 4000);

        return ToolResult::ok($content, ['topic' => $file, 'source' => $path]);
    }
}
