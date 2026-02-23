<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use App\Models\AuditLog; use Illuminate\Support\Facades\Hash;
class AuditCsvExportTest extends TestCase { use RefreshDatabase;
    public function test_audit_csv_export_headers_and_content(){
        $admin=User::factory()->create(['password'=>Hash::make('secret')]); $admin->assignRole('super_admin');
        AuditLog::factory()->count(3)->create(['user_id'=>$admin->id,'entity_type'=>'Invoice']);
        $this->actingAs($admin);
        $res=$this->get('/audit-logs?export=csv');
        $res->assertStatus(200);
        $this->assertTrue(str_contains($res->headers->get('Content-Type'),'text/csv'));
        $content=$res->getContent();
        $this->assertStringContainsString('ID,Fecha,Usuario,Entidad,Acción,Entidad_ID,IP,Cambios',$content);
        $this->assertStringContainsString('Invoice',$content);
    }
}
