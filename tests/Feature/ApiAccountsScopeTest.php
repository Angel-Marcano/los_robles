<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use App\Models\Account; use Illuminate\Support\Facades\Hash;
class ApiAccountsScopeTest extends TestCase {
    use RefreshDatabase;
    protected function seedAccounts(){
        // Creamos 3 cuentas genéricas
        Account::factory()->create(['name'=>'Global 1']);
        Account::factory()->create(['name'=>'Global 2']);
        Account::factory()->create(['name'=>'Global 3']);
    }
    public function test_super_admin_ve_todas(){
        $this->seedAccounts();
        $user=User::factory()->create(['password'=>Hash::make('secret')]);
        $user->assignRole('super_admin');
        $token=$this->postJson('/api/login',['email'=>$user->email,'password'=>'secret'])->json('token');
        $res=$this->withHeader('Authorization','Bearer '.$token)->getJson('/api/accounts');
        $res->assertStatus(200); $this->assertCount(3,$res->json('data'));
    }
    public function test_sin_rol_admin_no_autorizado(){
        $this->seedAccounts();
        $user=User::factory()->create(['password'=>Hash::make('secret')]);
        $token=$this->postJson('/api/login',['email'=>$user->email,'password'=>'secret'])->json('token');
        $this->withHeader('Authorization','Bearer '.$token)->getJson('/api/accounts')->assertStatus(403);
    }
}
