<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use App\Models\Account; use Illuminate\Support\Facades\Hash;
class ApiAccountsPaginationTest extends TestCase { use RefreshDatabase;
    public function test_accounts_pagination_sizes(){
        $admin=User::factory()->create(['password'=>Hash::make('secret')]);
        $admin->assignRole('super_admin');
        Account::factory()->count(60)->create();
        $token=$this->postJson('/api/login',['email'=>$admin->email,'password'=>'secret'])->json('token');
        // per_page 10
        $res10=$this->withHeader('Authorization','Bearer '.$token)->getJson('/api/accounts?per_page=10');
        $res10->assertStatus(200)->assertJsonStructure(['data','meta'=>['current_page','per_page','total','last_page','ratelimit'=>['limit','remaining']]]);
        $this->assertCount(10,$res10->json('data'));
        // per_page 50
        $res50=$this->withHeader('Authorization','Bearer '.$token)->getJson('/api/accounts?per_page=50');
        $res50->assertStatus(200);
        $this->assertCount(50,$res50->json('data'));
        // per_page inválido -> default 20
        $resInvalid=$this->withHeader('Authorization','Bearer '.$token)->getJson('/api/accounts?per_page=999');
        $this->assertEquals(20,$resInvalid->json('meta.per_page'));
    }
}
