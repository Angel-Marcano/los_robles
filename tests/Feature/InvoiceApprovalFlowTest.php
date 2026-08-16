<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Tower;
use App\Models\Apartment;
use App\Models\ExpenseItem;
use App\Models\Ownership;
use App\Services\InvoiceVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class InvoiceApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    // setUp() handled by TestCase

    private function setupData(): array
    {
        $tower = Tower::create(['name' => 'Torre A', 'active' => true, 'reserve_percent' => 5]);
        $apt1 = Apartment::create(['tower_id' => $tower->id, 'code' => 'A-01', 'active' => true, 'aliquot_percent' => 50]);
        $apt2 = Apartment::create(['tower_id' => $tower->id, 'code' => 'A-02', 'active' => true, 'aliquot_percent' => 50]);

        $owner1 = User::create([
            'name' => 'Owner 1', 'first_name' => 'O', 'last_name' => '1',
            'document_type' => 'cedula', 'document_number' => 'V-001',
            'email' => 'o1@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);
        $owner1->assignRole('owner');
        Ownership::create(['user_id' => $owner1->id, 'apartment_id' => $apt1->id, 'role' => 'owner', 'active' => true]);

        $owner2 = User::create([
            'name' => 'Owner 2', 'first_name' => 'O', 'last_name' => '2',
            'document_type' => 'cedula', 'document_number' => 'V-002',
            'email' => 'o2@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);
        $owner2->assignRole('owner');
        Ownership::create(['user_id' => $owner2->id, 'apartment_id' => $apt2->id, 'role' => 'owner', 'active' => true]);

        $expense = ExpenseItem::create(['name' => 'Agua', 'type' => 'aliquot', 'active' => true]);

        return [$tower, $apt1, $apt2, $owner1, $owner2, $expense];
    }

    public function test_draft_invoice_can_be_created()
    {
        [$tower, $apt1, $apt2, $owner1, $owner2, $expense] = $this->setupData();

        $invoice = Invoice::create([
            'tower_id' => $tower->id, 'period' => '2026-08',
            'status' => 'draft', 'due_date' => now()->addDays(10),
            'total_usd' => 100, 'total_ves' => 0,
        ]);

        $this->assertEquals('draft', $invoice->status);
        $this->assertNotNull($invoice->id);
    }

    public function test_invoice_gets_signature()
    {
        [$tower, $apt1, $apt2, $owner1, $owner2, $expense] = $this->setupData();

        $invoice = Invoice::create([
            'tower_id' => $tower->id, 'period' => '2026-08',
            'status' => 'pending', 'due_date' => now()->addDays(10),
            'total_usd' => 100, 'total_ves' => 0,
        ]);

        $verification = app(InvoiceVerificationService::class);
        $verification->ensureSignature($invoice);

        $this->assertNotNull($invoice->fresh()->invoice_signature);
        $this->assertNotNull($invoice->fresh()->signed_at);
    }

    public function test_invoice_verification_url_works()
    {
        [$tower, $apt1, $apt2, $owner1, $owner2, $expense] = $this->setupData();

        $invoice = Invoice::create([
            'tower_id' => $tower->id, 'period' => '2026-08',
            'status' => 'pending', 'due_date' => now()->addDays(10),
            'total_usd' => 100, 'total_ves' => 0,
        ]);

        $verification = app(InvoiceVerificationService::class);
        $url = $verification->verificationUrl($invoice);

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('/v/', $url);
    }

    public function test_invoice_status_label()
    {
        $invoice = new Invoice(['status' => 'draft']);
        $this->assertEquals('Borrador', $invoice->statusLabel());

        $invoice = new Invoice(['status' => 'pending']);
        $this->assertEquals('Pendiente', $invoice->statusLabel());

        $invoice = new Invoice(['status' => 'paid']);
        $this->assertEquals('Pagada', $invoice->statusLabel());
    }

    public function test_invoice_is_voided()
    {
        $invoice = new Invoice(['status' => 'voided']);
        $this->assertTrue($invoice->isVoided());

        $invoice = new Invoice(['status' => 'reissued']);
        $this->assertTrue($invoice->isVoided());

        $invoice = new Invoice(['status' => 'pending']);
        $this->assertFalse($invoice->isVoided());
    }
}