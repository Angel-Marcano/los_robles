<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\User; use App\Models\AuditLog; use Illuminate\Support\Facades\Hash;
class AuditLogFiltersTest extends TestCase { use RefreshDatabase;
    public function test_audit_filters_combined(){
        $admin=User::factory()->create(['password'=>Hash::make('secret')]); $admin->assignRole('super_admin');
        // Generar logs variados
        AuditLog::factory()->create(['entity_type'=>'Invoice','action'=>'created','user_id'=>$admin->id,'created_at'=>now()->subDays(2)]);
        AuditLog::factory()->create(['entity_type'=>'Invoice','action'=>'updated','user_id'=>$admin->id,'created_at'=>now()->subDay()]);
        AuditLog::factory()->create(['entity_type'=>'Account','action'=>'created','user_id'=>$admin->id]);
        $this->actingAs($admin);
        $from=now()->subDays(3)->format('Y-m-d'); $to=now()->format('Y-m-d');
        $res=$this->get('/audit-logs?entity_type=Invoice&action=updated&user_id='.$admin->id.'&date_from='.$from.'&date_to='.$to.'&per_page=10');
        $res->assertStatus(200);
        // Debe contener solo la combinación invoice+updated
        $this->assertStringContainsString('updated',$res->getContent());
        $this->assertStringNotContainsString('Account',$res->getContent());
    }
}
