<?php

namespace Tests\Unit\ChatbotTools;

use App\Models\User;
use App\Services\Chatbot\Tools\CreateSupportTicketTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSupportTicketToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_needs_info_when_description_missing(): void
    {
        $user = User::factory()->create();
        $tool = new CreateSupportTicketTool();
        $result = $tool->execute($user, [], []);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('describe', $result->message);
    }

    public function test_confirm_creates_ticket(): void
    {
        $user = User::factory()->create();
        $tool = new CreateSupportTicketTool();

        $result = $tool->confirm($user, [
            'tool' => 'create_support_ticket',
            'user_id' => $user->id,
            'category' => 'maintenance',
            'priority' => 'high',
            'description' => 'Hay una fuga en el pasillo del piso 3.',
        ]);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'category' => 'maintenance',
            'priority' => 'high',
            'status' => 'open',
        ]);
    }
}
