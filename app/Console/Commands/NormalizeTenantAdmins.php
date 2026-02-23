<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Condominium;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
// Usaremos el modelo configurado en permission.php para roles tenant

class NormalizeTenantAdmins extends Command
{
    protected $signature = 'tenants:normalize-admins {--dry-run}';
    protected $description = 'Normaliza usuarios admin antiguos (admin@admin) -> admin@admin.com y asegura rol super_admin en cada tenant activo.';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $condos = Condominium::where('active', true)->get();
        if ($condos->isEmpty()) { $this->warn('No hay condominios activos.'); return Command::SUCCESS; }
        foreach ($condos as $condo) {
            $this->line("[Tenant] {$condo->subdomain} ({$condo->db_name})");
            // Configurar conexión
            $base = config('database.connections.mysql');
            $tenantConfig = array_merge($base, ['database' => $condo->db_name]);
            config(['database.connections.tenant' => $tenantConfig]);
            DB::purge('tenant');
            app()->instance('currentCondominium', $condo); // habilita UsesTenantConnection
            try { DB::connection('tenant')->getPdo(); } catch (\Throwable $e) { $this->error('No se puede conectar: '.$e->getMessage()); continue; }
            if (!Schema::connection('tenant')->hasTable('users')) { $this->warn('Sin tabla users, omitiendo.'); continue; }
            // Asegurar roles base
            $roleClass = config('permission.models.role');
            if (Schema::connection('tenant')->hasTable('roles') && $roleClass && class_exists($roleClass)) {
                foreach (['super_admin','condo_admin','tower_admin','owner','co_owner','tenant'] as $r) {
                    $roleClass::firstOrCreate(['name'=>$r,'guard_name'=>'web']);
                }
            }
            $old = User::on('tenant')->where('email','admin@admin')->first();
            $new = User::on('tenant')->where('email','admin@admin.com')->first();
            if ($old && $new) {
                $this->line('Ya existe admin@admin.com y también admin@admin. Se asignará rol super_admin a ambos y se recomienda eliminar el antiguo manualmente.');
                if (!$dry) { $this->ensureSuper($old); $this->ensureSuper($new); }
                continue;
            }
            if ($old && !$new) {
                $this->info('Encontrado usuario legacy admin@admin. Renombrando a admin@admin.com.');
                if (!$dry) { $old->email = 'admin@admin.com'; $old->save(); $this->ensureSuper($old); }
                continue;
            }
            if (!$old && !$new) {
                $this->info('No existe admin. Creando admin@admin.com.');
                if ($dry) { continue; }
                $u = User::on('tenant')->create([
                    'name' => 'Administrador',
                    'email' => 'admin@admin.com',
                    'password' => bcrypt('1234'),
                    'active' => true,
                ]);
                $this->ensureSuper($u);
                continue;
            }
            if ($new) {
                $this->line('Usuario admin@admin.com ya presente. Asegurando rol super_admin.');
                if (!$dry) { $this->ensureSuper($new); }
            }
        }
        $this->info('Normalización completada.'.($dry?' (dry-run)':''));
        return Command::SUCCESS;
    }

    private function ensureSuper(User $u): void
    {
    $roleClass = config('permission.models.role');
    if (!$roleClass || !class_exists($roleClass) || !Schema::connection('tenant')->hasTable('roles')) { $this->warn('Roles no disponibles para asignar super_admin.'); return; }
    $role = $roleClass::firstOrCreate(['name'=>'super_admin','guard_name'=>'web']);
        if (!$u->hasRole('super_admin')) { $u->assignRole($role); $this->line('Rol super_admin asignado al usuario ID '.$u->id); }
    }
}
