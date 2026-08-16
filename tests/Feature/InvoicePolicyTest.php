<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Tower;
use App\Models\Apartment;
use App\Models\Ownership;
use App\Models\Condominium;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    // setUp() handled by TestCase (runs tenant migrations + seeds roles)

    private function createOwnerWithApartment(): array
    {
        $tower = Tower::create(['name' => 'Torre A', 'active' => true, 'reserve_percent' => 5]);
        $apt1 = Apartment::create(['tower_id' => $tower->id, 'code' => 'A-01', 'active' => true, 'aliquot_percent' => 2.5]);
        $apt2 = Apartment::create(['tower_id' => $tower->id, 'code' => 'A-02', 'active' => true, 'aliquot_percent' => 2.5]);

        $owner = User::create([
            'name' => 'Owner Test', 'first_name' => 'Owner', 'last_name' => 'Test',
            'document_type' => 'cedula', 'document_number' => 'V-111',
            'email' => 'owner@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);
        $owner->assignRole('owner');

        Ownership::create(['user_id' => $owner->id, 'apartment_id' => $apt1->id, 'role' => 'owner', 'active' => true]);

        return [$owner, $apt1, $apt2, $tower];
    }

    private function createAdmin(): User
    {
        $admin = User::create([
            'name' => 'Admin Test', 'first_name' => 'Admin', 'last_name' => 'Test',
            'document_type' => 'cedula', 'document_number' => 'V-222',
            'email' => 'admin@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);
        $admin->assignRole('condo_admin');
        return $admin;
    }

    public function test_owner_can_view_own_invoice()
    {
        [$owner, $apt1, $apt2, $tower] = $this->createOwnerWithApartment();

        $invoice = Invoice::create([
            'apartment_id' => $apt1->id, 'tower_id' => $tower->id,
            'period' => '2026-08', 'status' => 'pending',
            'due_date' => now()->addDays(10), 'total_usd' => 100, 'total_ves' => 0,
        ]);

        $this->assertTrue($owner->can('view', $invoice));
    }

    public function test_owner_cannot_view_other_apartment_invoice()
    {
        [$owner, $apt1, $apt2, $tower] = $this->createOwnerWithApartment();

        $invoice = Invoice::create([
            'apartment_id' => $apt2->id, 'tower_id' => $tower->id,
            'period' => '2026-08', 'status' => 'pending',
            'due_date' => now()->addDays(10), 'total_usd' => 100, 'total_ves' => 0,
        ]);

        $this->assertFalse($owner->can('view', $invoice));
    }

    public function test_admin_can_view_any_invoice()
    {
        [$owner, $apt1, $apt2, $tower] = $this->createOwnerWithApartment();
        $admin = $this->createAdmin();

        $invoice = Invoice::create([
            'apartment_id' => $apt2->id, 'tower_id' => $tower->id,
            'period' => '2026-08', 'status' => 'pending',
            'due_date' => now()->addDays(10), 'total_usd' => 100, 'total_ves' => 0,
        ]);

        $this->assertTrue($admin->can('view', $invoice));
    }

    public function test_owner_cannot_create_invoices()
    {
        [$owner, $apt1, $apt2, $tower] = $this->createOwnerWithApartment();
        $this->assertFalse($owner->can('create', Invoice::class));
    }

    public function test_admin_can_create_invoices()
    {
        $admin = $this->createAdmin();
        $this->assertTrue($admin->can('create', Invoice::class));
    }

    public function test_owner_cannot_view_parent_invoice()
    {
        [$owner, $apt1, $apt2, $tower] = $this->createOwnerWithApartment();

        $parent = Invoice::create([
            'apartment_id' => null, 'tower_id' => $tower->id,
            'period' => '2026-08', 'status' => 'pending',
            'due_date' => now()->addDays(10), 'total_usd' => 500, 'total_ves' => 0,
        ]);

        $this->assertFalse($owner->can('view', $parent));
    }
}