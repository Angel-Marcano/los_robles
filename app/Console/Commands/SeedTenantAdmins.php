<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Condominium;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class SeedTenantAdmins extends Command
{
    protected $signature = 'tenants:seed-admins {--password=1234}';
    protected $description = 'Crea usuario admin@admin.com con rol super_admin en cada BD tenant que no lo tenga.';

    public function handle(): int
    {
        $password = $this->option('password');
        $condos = Condominium::where('active',true)->get();
        if ($condos->isEmpty()) {
            $this->warn('No hay condominios activos.');
            return Command::SUCCESS;
        }
        foreach ($condos as $condo) {
            $this->line('Procesando condominio: '.$condo->subdomain.' (DB: '.$condo->db_name.')');
            // Configurar conexión tenant
            $base = config('database.connections.mysql');
            $tenantConfig = array_merge($base, ['database' => $condo->db_name]);
            config(['database.connections.tenant' => $tenantConfig]);
            DB::purge('tenant');
            // Vincular el condominio actual al contenedor para que UsesTenantConnection resuelva correctamente
            app()->instance('currentCondominium', $condo);
            try { DB::connection('tenant')->getPdo(); } catch (\Throwable $e) { $this->error('No se puede conectar: '.$e->getMessage()); continue; }
            // Verificar que exista tabla users
            if (!Schema::connection('tenant')->hasTable('users')) {
                $this->warn('Tabla users no existe en tenant '.$condo->subdomain.'. Ejecuta tenants:migrate primero.');
                continue;
            }
            // Asegurar roles base si la tabla existe
            if (Schema::connection('tenant')->hasTable('roles')) {
                $roleClass = config('permission.models.role');
                if ($roleClass && class_exists($roleClass)) {
                    $roles = ['super_admin','condo_admin','tower_admin','owner','co_owner','tenant'];
                    foreach ($roles as $r) { $roleClass::firstOrCreate(['name'=>$r,'guard_name'=>'web']); }
                }
            }
            $adminEmail = 'admin@admin.com';
            // Forzar conexión tenant para evitar fallback si el binding falla
            $user = User::on('tenant')->where('email',$adminEmail)->first();
            if (!$user) {
                $user = User::on('tenant')->create([
                    'name' => 'Administrador',
                    'email' => $adminEmail,
                    'password' => bcrypt($password),
                    'active' => true,
                ]);
                $this->info('Super admin creado en '.$condo->subdomain.': '.$adminEmail.' / '.$password);
            } else {
                $this->line('Usuario super admin ya existe, se asegura rol.');
            }
            if (Schema::connection('tenant')->hasTable('roles')) {
                try {
                    $roleClass = config('permission.models.role');
                    if ($roleClass && class_exists($roleClass)) {
                        $role = $roleClass::firstOrCreate(['name'=>'super_admin','guard_name'=>'web']);
                        if (!$user->hasRole('super_admin')) {
                            $user->assignRole($role);
                            $this->line('Rol super_admin asignado.');
                        }
                    }
                } catch (\Throwable $e) {
                    $this->warn('No se pudo asignar rol super_admin: '.$e->getMessage());
                }
            }
        }
        $this->info('Proceso completado.');
        return Command::SUCCESS;
    }
}
