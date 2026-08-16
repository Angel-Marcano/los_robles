<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tower;
use App\Models\Apartment;
use App\Models\Ownership;
use App\Models\Assembly;
use App\Models\AssemblyOption;
use App\Models\AssemblyVote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AssemblyTest extends TestCase
{
    use RefreshDatabase;

    // setUp() handled by TestCase

    private function setupData(): array
    {
        $tower = Tower::create(['name' => 'Torre A', 'active' => true, 'reserve_percent' => 5]);
        $apt1 = Apartment::create(['tower_id' => $tower->id, 'code' => 'A-01', 'active' => true, 'aliquot_percent' => 50]);
        $apt2 = Apartment::create(['tower_id' => $tower->id, 'code' => 'A-02', 'active' => true, 'aliquot_percent' => 50]);

        $owner = User::create([
            'name' => 'Owner', 'first_name' => 'O', 'last_name' => 'Test',
            'document_type' => 'cedula', 'document_number' => 'V-001',
            'email' => 'owner@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);
        $owner->assignRole('owner');
        Ownership::create(['user_id' => $owner->id, 'apartment_id' => $apt1->id, 'role' => 'owner', 'active' => true]);

        $tenant = User::create([
            'name' => 'Tenant', 'first_name' => 'T', 'last_name' => 'Test',
            'document_type' => 'cedula', 'document_number' => 'V-002',
            'email' => 'tenant@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);
        $tenant->assignRole('tenant');
        Ownership::create(['user_id' => $tenant->id, 'apartment_id' => $apt2->id, 'role' => 'tenant', 'active' => true]);

        $admin = User::create([
            'name' => 'Admin', 'first_name' => 'A', 'last_name' => 'Test',
            'document_type' => 'cedula', 'document_number' => 'V-003',
            'email' => 'admin@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);
        $admin->assignRole('condo_admin');

        return [$tower, $apt1, $apt2, $owner, $tenant, $admin];
    }

    private function createAssembly(): Assembly
    {
        $assembly = Assembly::create([
            'title' => 'Aprobar presupuesto 2026',
            'description' => 'Votación sobre el presupuesto anual',
            'scope' => 'condo',
            'vote_type' => 'public',
            'quorum_type' => 'simple',
            'quorum_value' => 50,
            'weight_mode' => 'equal',
            'closes_at' => now()->addDays(7),
            'status' => 'open',
            'created_by' => 1,
        ]);

        AssemblyOption::create(['assembly_id' => $assembly->id, 'label' => 'Aprobar', 'sort_order' => 0]);
        AssemblyOption::create(['assembly_id' => $assembly->id, 'label' => 'Rechazar', 'sort_order' => 1]);

        return $assembly;
    }

    public function test_assembly_can_be_created()
    {
        $assembly = $this->createAssembly();
        $this->assertNotNull($assembly->id);
        $this->assertEquals('open', $assembly->status);
        $this->assertEquals(2, $assembly->options()->count());
    }

    public function test_owner_is_eligible_voter()
    {
        [$tower, $apt1, $apt2, $owner, $tenant, $admin] = $this->setupData();
        $assembly = $this->createAssembly();

        $eligible = $assembly->eligibleVoters();
        $this->assertTrue($eligible->contains('id', $owner->id));
    }

    public function test_tenant_is_not_eligible_voter()
    {
        [$tower, $apt1, $apt2, $owner, $tenant, $admin] = $this->setupData();
        $assembly = $this->createAssembly();

        $eligible = $assembly->eligibleVoters();
        $this->assertFalse($eligible->contains('id', $tenant->id));
    }

    public function test_owner_can_vote()
    {
        [$tower, $apt1, $apt2, $owner, $tenant, $admin] = $this->setupData();
        $assembly = $this->createAssembly();
        $option = $assembly->options->first();

        AssemblyVote::create([
            'assembly_id' => $assembly->id,
            'user_id' => $owner->id,
            'option_id' => $option->id,
            'weight' => 1.0,
            'voted_at' => now(),
        ]);

        $this->assertTrue($assembly->hasVoted($owner->id));
        $this->assertEquals(1, $assembly->totalVotes());
    }

    public function test_user_cannot_vote_twice()
    {
        [$tower, $apt1, $apt2, $owner, $tenant, $admin] = $this->setupData();
        $assembly = $this->createAssembly();
        $option = $assembly->options->first();

        AssemblyVote::create([
            'assembly_id' => $assembly->id, 'user_id' => $owner->id,
            'option_id' => $option->id, 'weight' => 1.0, 'voted_at' => now(),
        ]);

        // Intentar votar de nuevo debería fallar por unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        AssemblyVote::create([
            'assembly_id' => $assembly->id, 'user_id' => $owner->id,
            'option_id' => $assembly->options->last()->id,
            'weight' => 1.0, 'voted_at' => now(),
        ]);
    }

    public function test_assembly_results_calculate_percentages()
    {
        [$tower, $apt1, $apt2, $owner, $tenant, $admin] = $this->setupData();
        $assembly = $this->createAssembly();
        $opt1 = $assembly->options->first();
        $opt2 = $assembly->options->last();

        // Owner vota "Aprobar"
        AssemblyVote::create([
            'assembly_id' => $assembly->id, 'user_id' => $owner->id,
            'option_id' => $opt1->id, 'weight' => 1.0, 'voted_at' => now(),
        ]);

        $results = $assembly->results();
        $this->assertCount(2, $results);
        $this->assertEquals(100, $results[0]['percentage']);
        $this->assertEquals(0, $results[1]['percentage']);
    }

    public function test_assembly_is_open()
    {
        $assembly = $this->createAssembly();
        $this->assertTrue($assembly->isOpen());
    }

    public function test_closed_assembly_is_not_open()
    {
        $assembly = $this->createAssembly();
        $assembly->update(['status' => 'closed']);
        $this->assertFalse($assembly->isOpen());
        $this->assertTrue($assembly->isClosed());
    }

    public function test_assembly_with_past_close_date_is_closed()
    {
        $assembly = $this->createAssembly();
        $assembly->update(['closes_at' => now()->subDay()]);
        $this->assertTrue($assembly->isClosed());
    }

    public function test_tower_scoped_assembly_excludes_other_towers()
    {
        [$tower, $apt1, $apt2, $owner, $tenant, $admin] = $this->setupData();
        $tower2 = Tower::create(['name' => 'Torre B', 'active' => true, 'reserve_percent' => 5]);
        $apt3 = Apartment::create(['tower_id' => $tower2->id, 'code' => 'B-01', 'active' => true, 'aliquot_percent' => 100]);

        $owner2 = User::create([
            'name' => 'Owner 2', 'first_name' => 'O', 'last_name' => '2',
            'document_type' => 'cedula', 'document_number' => 'V-010',
            'email' => 'o2@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);
        $owner2->assignRole('owner');
        Ownership::create(['user_id' => $owner2->id, 'apartment_id' => $apt3->id, 'role' => 'owner', 'active' => true]);

        $assembly = Assembly::create([
            'title' => 'Votación Torre A', 'scope' => 'tower',
            'tower_ids' => [$tower->id],
            'vote_type' => 'public', 'quorum_type' => 'simple', 'quorum_value' => 50,
            'weight_mode' => 'equal', 'closes_at' => now()->addDays(7),
            'status' => 'open', 'created_by' => 1,
        ]);
        AssemblyOption::create(['assembly_id' => $assembly->id, 'label' => 'Sí', 'sort_order' => 0]);
        AssemblyOption::create(['assembly_id' => $assembly->id, 'label' => 'No', 'sort_order' => 1]);

        $eligible = $assembly->eligibleVoters();
        $this->assertTrue($eligible->contains('id', $owner->id));
        $this->assertFalse($eligible->contains('id', $owner2->id));
    }

    public function test_aliquot_weight_mode()
    {
        [$tower, $apt1, $apt2, $owner, $tenant, $admin] = $this->setupData();
        $assembly = Assembly::create([
            'title' => 'Votación alícuota', 'scope' => 'condo',
            'vote_type' => 'public', 'quorum_type' => 'none', 'quorum_value' => 0,
            'weight_mode' => 'aliquot', 'closes_at' => now()->addDays(7),
            'status' => 'open', 'created_by' => 1,
        ]);
        AssemblyOption::create(['assembly_id' => $assembly->id, 'label' => 'Sí', 'sort_order' => 0]);
        AssemblyOption::create(['assembly_id' => $assembly->id, 'label' => 'No', 'sort_order' => 1]);

        // El owner tiene alícuota 50%
        $ownership = $owner->ownerships()->first();
        $this->assertEquals(50, (float) $ownership->apartment->aliquot_percent);
    }
}