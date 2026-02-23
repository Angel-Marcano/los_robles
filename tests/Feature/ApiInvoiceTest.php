<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use App\Models\Invoice; use Illuminate\Support\Facades\Hash;
class ApiInvoiceTest extends TestCase {
    use RefreshDatabase;
    public function test_invoices_index_paginates_and_filters(){
        $user=User::factory()->create(['password'=>Hash::make('secret')]);
        // Crear facturas variadas
        Invoice::factory()->count(3)->create(['status'=>'pending','condominium_id'=>1]);
        Invoice::factory()->count(2)->create(['status'=>'paid','condominium_id'=>2]);
        $login=$this->postJson('/api/login',['email'=>$user->email,'password'=>'secret']);
        $token=$login->json('token');
        $this->withHeader('Authorization','Bearer '.$token);
        $res=$this->getJson('/api/invoices?status=pending&per_page=2');
        $res->assertStatus(200)->assertJsonStructure(['data','meta'=>['current_page','per_page','total','last_page']]);
        $this->assertCount(2,$res->json('data')); // paginación
    }
    public function test_invoices_filter_by_created_range(){
        $user=User::factory()->create(['password'=>Hash::make('secret')]);
        Invoice::factory()->create(['status'=>'pending','condominium_id'=>1,'created_at'=>now()->subDays(10)]);
        Invoice::factory()->create(['status'=>'pending','condominium_id'=>1,'created_at'=>now()->subDays(2)]);
        $login=$this->postJson('/api/login',['email'=>$user->email,'password'=>'secret']);
        $token=$login->json('token');
        $this->withHeader('Authorization','Bearer '.$token);
        $from=now()->subDays(5)->format('Y-m-d');
        $to=now()->format('Y-m-d');
        $res=$this->getJson('/api/invoices?created_from='.$from.'&created_to='.$to.'&status=pending');
        $res->assertStatus(200);
        $this->assertEquals(1,count($res->json('data')),'Debe filtrar solo la factura reciente');
    }
}
