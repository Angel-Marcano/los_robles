<?php

namespace Tests\Unit\ChatbotTools;

use App\Models\Apartment;
use App\Models\Invoice;
use App\Models\Ownership;
use App\Models\Tower;
use App\Models\User;
use App\Services\Chatbot\Tools\GetInvoiceBalanceTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetInvoiceBalanceToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_balance_for_owned_apartment(): void
    {
        $user = User::factory()->create();
        $tower = Tower::factory()->create();
        $apt = Apartment::factory()->create(['tower_id' => $tower->id]);
        Ownership::create(['user_id' => $user->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'active' => true]);

        Invoice::create([
            'period' => '2026-07',
            'apartment_id' => $apt->id,
            'tower_id' => $tower->id,
            'condominium_id' => $tower->condominium_id,
            'created_by' => $user->id,
            'due_date' => now()->addDays(10),
            'total_usd' => 120.00,
            'total_ves' => 0,
            'exchange_rate_used' => 50.00,
            'status' => 'pending',
        ]);

        $tool = new GetInvoiceBalanceTool();
        $result = $tool->execute($user, [], ['default_apartment_id' => $apt->id]);

        $this->assertTrue($result->success);
        $this->assertStringContainsString('120.00', $result->message);
        $this->assertSame(120.0, $result->data['total_usd']);
    }

    public function test_blocks_other_user_apartment(): void
    {
        $user = User::factory()->create();
        $otherApt = Apartment::factory()->create();

        $tool = new GetInvoiceBalanceTool();
        $result = $tool->execute($user, ['apartment_id' => $otherApt->id], []);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('permiso', $result->message);
    }
}
