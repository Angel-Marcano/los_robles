<?php

namespace Tests\Unit\ChatbotTools;

use App\Models\Apartment;
use App\Models\Invoice;
use App\Models\Ownership;
use App\Models\Tower;
use App\Models\User;
use App\Services\Chatbot\Tools\ReportPaymentTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPaymentToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_needs_confirmation_with_valid_data(): void
    {
        $user = User::factory()->create();
        $tower = Tower::factory()->create();
        $apt = Apartment::factory()->create(['tower_id' => $tower->id]);
        Ownership::create(['user_id' => $user->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'active' => true]);

        $invoice = Invoice::create([
            'period' => '2026-07',
            'apartment_id' => $apt->id,
            'tower_id' => $tower->id,
            'condominium_id' => $tower->condominium_id,
            'created_by' => $user->id,
            'due_date' => now()->addDays(5),
            'total_usd' => 80.00,
            'total_ves' => 0,
            'exchange_rate_used' => 50.00,
            'status' => 'pending',
        ]);

        $tool = new ReportPaymentTool();
        $result = $tool->execute($user, [
            'invoice_id' => $invoice->id,
            'amount_usd' => 80,
            'payment_method' => 'transferencia',
            'reference' => 'REF123',
            'paid_at' => now()->toDateString(),
        ], ['default_apartment_id' => $apt->id]);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->confirmationPrompt);
        $this->assertSame('report_payment', $result->data['tool']);
    }

    public function test_confirm_creates_payment_report(): void
    {
        $user = User::factory()->create();
        $tower = Tower::factory()->create();
        $apt = Apartment::factory()->create(['tower_id' => $tower->id]);
        $invoice = Invoice::create([
            'period' => '2026-07',
            'apartment_id' => $apt->id,
            'tower_id' => $tower->id,
            'condominium_id' => $tower->condominium_id,
            'created_by' => $user->id,
            'due_date' => now()->addDays(5),
            'total_usd' => 50.00,
            'total_ves' => 0,
            'exchange_rate_used' => 50.00,
            'status' => 'pending',
        ]);

        $tool = new ReportPaymentTool();
        $result = $tool->confirm($user, [
            'tool' => 'report_payment',
            'invoice_id' => $invoice->id,
            'apartment_id' => $apt->id,
            'user_id' => $user->id,
            'amount_usd' => 50,
            'amount_ves' => 0,
            'payment_method' => 'zelle',
            'reference' => 'ZELLE-1',
            'paid_at' => now()->toDateString(),
            'exchange_rate_used' => 50,
        ]);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('payment_reports', [
            'invoice_id' => $invoice->id,
            'reference_number' => 'ZELLE-1',
            'status' => 'reported',
        ]);
    }
}
