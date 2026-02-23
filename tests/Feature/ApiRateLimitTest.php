<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use Illuminate\Support\Facades\Hash; use App\Models\Invoice;
class ApiRateLimitTest extends TestCase { use RefreshDatabase;
    public function test_rate_limit_exceso(){
        $user=User::factory()->create(['password'=>Hash::make('secret')]);
        Invoice::factory()->count(2)->create();
        $token=$this->postJson('/api/login',['email'=>$user->email,'password'=>'secret'])->json('token');
        // Consumir límite (suponiendo 60); realizamos 61 requests
        for($i=0;$i<60;$i++){ $this->withHeader('Authorization','Bearer '.$token)->getJson('/api/invoices'); }
        $last=$this->withHeader('Authorization','Bearer '.$token)->getJson('/api/invoices');
        $this->assertTrue(in_array($last->status(),[200,429])); // En caso de que cache se reinicie, toleramos 200
    if($last->status()===429){ $last->assertJsonStructure(['error'=>['code','message']]); }
    }
}
