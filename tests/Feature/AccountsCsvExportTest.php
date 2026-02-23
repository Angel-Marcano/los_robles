<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use App\Models\Account; use Illuminate\Support\Facades\Hash;
class AccountsCsvExportTest extends TestCase { use RefreshDatabase;
    public function test_accounts_csv_export(){
        $admin=User::factory()->create(['password'=>Hash::make('secret')]); $admin->assignRole('super_admin');
        Account::factory()->count(10)->create();
        $token=$this->postJson('/api/login',['email'=>$admin->email,'password'=>'secret'])->json('token');
        $res=$this->withHeader('Authorization','Bearer '.$token)->get('/api/accounts?export=csv');
        $res->assertStatus(200);
        $this->assertTrue(str_contains($res->headers->get('Content-Type'),'text/csv'));
        $content=$res->getContent();
        $this->assertStringContainsString('ID,Nombre,Owner_Type,Owner_ID,Balance_USD,Balance_VES',$content);
    }
}
