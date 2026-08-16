<?php

namespace Tests\Unit\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\User;
use App\Services\Chatbot\HistoryLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryLoaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_recent_messages_in_openai_format(): void
    {
        $user = User::factory()->create();
        $loader = new HistoryLoader();

        ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => 'sess_abc',
            'input_sanitized' => 'Hola',
            'output_raw' => 'Hola, ¿en qué puedo ayudarte?',
            'intent' => 'greeting',
        ]);

        ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => 'sess_abc',
            'input_sanitized' => '¿Cuánto debo?',
            'output_raw' => 'Tu saldo es 120 USD.',
            'intent' => 'balance',
        ]);

        $history = $loader->load($user, 'sess_abc', 10);

        $this->assertCount(4, $history);
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame('Hola', $history[0]['content']);
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertSame('Hola, ¿en qué puedo ayudarte?', $history[1]['content']);
        $this->assertSame('user', $history[2]['role']);
        $this->assertSame('¿Cuánto debo?', $history[2]['content']);
        $this->assertSame('assistant', $history[3]['role']);
        $this->assertSame('Tu saldo es 120 USD.', $history[3]['content']);
    }

    public function test_respects_limit(): void
    {
        $user = User::factory()->create();
        $loader = new HistoryLoader();

        for ($i = 1; $i <= 5; $i++) {
            ChatbotConversation::create([
                'user_id' => $user->id,
                'session_id' => 'sess_limit',
                'input_sanitized' => "Mensaje $i",
                'output_raw' => "Respuesta $i",
                'intent' => 'faq',
            ]);
        }

        $history = $loader->load($user, 'sess_limit', 2);

        // 2 mensajes = 1 user + 1 assistant (o 2 pares si se cuenta por pares)
        $this->assertLessThanOrEqual(4, count($history));
        $this->assertSame('Mensaje 5', $history[count($history) - 2]['content']);
    }

    public function test_ignores_other_sessions_and_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $loader = new HistoryLoader();

        ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => 'sess_user',
            'input_sanitized' => 'Mío',
            'output_raw' => 'Ok',
            'intent' => 'faq',
        ]);

        ChatbotConversation::create([
            'user_id' => $other->id,
            'session_id' => 'sess_user',
            'input_sanitized' => 'Otro usuario',
            'output_raw' => 'No',
            'intent' => 'faq',
        ]);

        ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => 'sess_other',
            'input_sanitized' => 'Otra sesión',
            'output_raw' => 'No',
            'intent' => 'faq',
        ]);

        $history = $loader->load($user, 'sess_user', 10);

        $this->assertCount(2, $history);
        $this->assertSame('Mío', $history[0]['content']);
    }
}
