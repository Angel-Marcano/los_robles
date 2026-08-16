<?php

namespace Tests\Unit\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\User;
use App\Services\Chatbot\ConfirmationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_confirmation_positive(): void
    {
        $manager = new ConfirmationManager();
        $this->assertSame('confirmed', $manager->parseConfirmation('sí'));
        $this->assertSame('confirmed', $manager->parseConfirmation('confirmo'));
        $this->assertSame('confirmed', $manager->parseConfirmation('ok'));
    }

    public function test_parses_confirmation_negative(): void
    {
        $manager = new ConfirmationManager();
        $this->assertSame('rejected', $manager->parseConfirmation('no'));
        $this->assertSame('rejected', $manager->parseConfirmation('cancelar'));
    }

    public function test_parses_confirmation_unknown(): void
    {
        $manager = new ConfirmationManager();
        $this->assertSame('unknown', $manager->parseConfirmation('tal vez'));
    }

    public function test_stores_and_retrieves_pending_action(): void
    {
        $user = User::factory()->create();
        $manager = new ConfirmationManager();

        $manager->storePendingAction($user, 'sess_123', ['tool' => 'report_payment', 'amount' => 100]);
        $pending = $manager->getPendingAction($user, 'sess_123');

        $this->assertNotNull($pending);
        $this->assertSame('report_payment', $pending['tool']);
    }

    public function test_expired_pending_action_returns_null(): void
    {
        $user = User::factory()->create();
        $manager = new ConfirmationManager();

        ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => 'sess_old',
            'is_action_pending' => true,
            'pending_action' => ['tool' => 'report_payment'],
            'pending_action_expires_at' => now()->subMinute(),
        ]);

        $this->assertNull($manager->getPendingAction($user, 'sess_old'));
    }
}
