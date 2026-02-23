<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Condominium;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SeedTenantRoles extends Command
{
    protected $signature = 'tenants:seed-roles';
    protected $description = 'Ejecuta TenantRolesSeeder en cada base de datos tenant activa (idempotente).';

    public function handle(): int
    {
        $condos = Condominium::where('active', true)->get();
        if ($condos->isEmpty()) {
            $this->warn('No hay condominios activos.');
            return Command::SUCCESS;
        }
        foreach ($condos as $condo) {
            $this->line("Procesando condominio {$condo->subdomain} (DB: {$condo->db_name})");
            $base = config('database.connections.mysql');
            $tenantConfig = array_merge($base, ['database' => $condo->db_name]);
            config(['database.connections.tenant' => $tenantConfig]);
            DB::purge('tenant');
            app()->instance('currentCondominium', $condo); // Para que trait y modelos tenant usen la conexión adecuada
            try { DB::connection('tenant')->getPdo(); } catch (\Throwable $e) { $this->error('No se pudo conectar: '.$e->getMessage()); continue; }
            if (!Schema::connection('tenant')->hasTable('roles')) {
                $this->warn('Tabla roles no existe todavía, ejecuta tenants:migrate para este tenant primero.');
                continue;
            }
            $this->info('Seed de roles...');
            $roleClass = config('permission.models.role');
            if ($roleClass && class_exists($roleClass)) {
                foreach (['super_admin','condo_admin','tower_admin','owner','co_owner','tenant'] as $r) {
                    $roleClass::firstOrCreate(['name'=>$r,'guard_name'=>'web']);
                }
                $this->line('Roles verificados/creados.');
            } else {
                $this->warn('Modelo de roles no disponible.');
            }
        }
        $this->info('Roles sembrados en todos los tenants.');
        return Command::SUCCESS;
    }
}
