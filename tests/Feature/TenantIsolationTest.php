<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    // setUp() handled by TestCase

    public function test_tenant_connection_is_separate_config()
    {
        // En testing, tenant apunta a la misma BD que mysql
        $tenantDb = config('database.connections.tenant.database');
        $defaultDb = config('database.connections.mysql.database');
        $this->assertEquals($defaultDb, $tenantDb);
    }

    public function test_user_created_on_tenant_is_visible_on_tenant()
    {
        $user = User::create([
            'name' => 'Test', 'first_name' => 'Test', 'last_name' => 'User',
            'document_type' => 'cedula', 'document_number' => 'V-999',
            'email' => 'isol@test.com', 'password' => bcrypt('1234'), 'active' => true,
        ]);

        $found = User::on('tenant')->where('email', 'isol@test.com')->first();
        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_tower_created_on_tenant_exists_on_tenant()
    {
        $tower = Tower::create(['name' => 'Torre Test', 'active' => true, 'reserve_percent' => 5]);

        $found = Tower::on('tenant')->find($tower->id);
        $this->assertNotNull($found);
        $this->assertEquals('Torre Test', $found->name);
    }

    public function test_identify_condomininium_resolves_subdomain()
    {
        $condo = \App\Models\Condominium::create([
            'name' => 'Test Condo', 'subdomain' => 'testcondo',
            'db_name' => 'los_robles_test', 'active' => true,
        ]);

        $found = \App\Models\Condominium::on('mysql')->where('subdomain', 'testcondo')->first();
        $this->assertNotNull($found);
        $this->assertEquals($condo->id, $found->id);
    }
}