<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use App\Models\Invoice; use Illuminate\Support\Facades\Hash;
class InvoicesCsvExportTest extends TestCase { use RefreshDatabase;
    public function test_invoices_csv_export(){
        $user=User::factory()->create(['password'=>Hash::make('secret')]); $user->assignRole('super_admin');
        Invoice::factory()->count(5)->create(['status'=>'pending']);
        $token=$this->postJson('/api/login',['email'=>$user->email,'password'=>'secret'])->json('token');
        $res=$this->withHeader('Authorization','Bearer '.$token)->get('/api/invoices?export=csv');
        $res->assertStatus(200);
        $this->assertTrue(str_contains($res->headers->get('Content-Type'),'text/csv'));
        $content=$res->getContent();
        $this->assertStringContainsString('ID,Periodo,Estado,Total_USD,Total_VES,Mora_USD,Mora_VES,Creado,Vence',$content);
    }
}
