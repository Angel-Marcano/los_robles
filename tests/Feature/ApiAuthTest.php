<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use Illuminate\Support\Facades\Hash;
class ApiAuthTest extends TestCase {
    use RefreshDatabase;
    public function test_login_returns_token_and_roles(){
        $user=User::factory()->create(['password'=>Hash::make('secret')]);
        $res=$this->postJson('/api/login',['email'=>$user->email,'password'=>'secret']);
        $res->assertStatus(200)->assertJsonStructure(['token','user'=>['id','name','email','roles','permissions']]);
    }
    public function test_me_requires_auth(){
        $this->getJson('/api/me')->assertStatus(401);
    }
    public function test_me_requires_bearer_format(){
        $user=User::factory()->create(['password'=>Hash::make('secret')]);
        $login=$this->postJson('/api/login',['email'=>$user->email,'password'=>'secret']);
        $token=$login->json('token');
        // Enviar header sin Bearer debe fallar (Sanctum espera formato Bearer)
        $this->withHeader('Authorization',$token)->getJson('/api/me')->assertStatus(401);
    }
}
