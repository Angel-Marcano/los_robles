<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Condominium;

class CreateTenant extends Command
{
    protected $signature = 'tenants:create {name} {subdomain} {--db=} {--seed}';
    protected $description = 'Crea un nuevo condominio (tenant): BD, registro, migraciones y seeding opcional.';

    public function handle(): int
    {
        $name = $this->argument('name');
        $subdomain = strtolower($this->argument('subdomain'));
        $explicitDb = $this->option('db');
        $seed = (bool)$this->option('seed');

        // Validaciones básicas
        if (Condominium::where('subdomain', $subdomain)->exists()) {
            $this->error("Ya existe un condominio con subdomain '{$subdomain}'.");
            return Command::FAILURE;
        }

        // Generar nombre de BD si no se pasa
        $dbName = $explicitDb ?: $this->generateDatabaseName($subdomain);
        if ($this->databaseExists($dbName)) {
            $this->error("La base de datos '{$dbName}' ya existe. Usa --db para definir otra, o elimínala manualmente.");
            return Command::FAILURE;
        }

        // Crear BD física
        try {
            DB::statement("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            $this->error('Error creando la base de datos: '.$e->getMessage());
            return Command::FAILURE;
        }
        $this->info("Base de datos '{$dbName}' creada.");

        // Registrar condominio en landlord
        $condominium = Condominium::create([
            'name' => $name,
            'subdomain' => $subdomain,
            'db_name' => $dbName,
            'active' => true,
        ]);
        $this->info("Condominio registrado: ID {$condominium->id}");

        // Configurar conexión tenant
        $base = config('database.connections.mysql');
        $tenantConfig = array_merge($base, ['database' => $dbName]);
        config(['database.connections.tenant' => $tenantConfig]);
        DB::purge('tenant');
        try { DB::connection('tenant')->getPdo(); } catch (\Throwable $e) {
            $this->error('No se pudo conectar a la BD recién creada: '.$e->getMessage());
            return Command::FAILURE;
        }

        // Ejecutar migraciones tenant
        $this->info('Ejecutando migraciones tenant...');
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        // Seed de roles base (siempre) antes de crear el usuario
        $this->info('Seeding roles base del tenant...');
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => 'Database\\Seeders\\TenantRolesSeeder',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        // Crear usuario admin por defecto (siempre) y asignar rol si existe Spatie
        try {
            $this->info('Creando usuario super admin por defecto...');
            // Estandarizamos el correo a admin@admin.com
            $adminEmail = 'admin@admin.com';
            $existing = \App\Models\User::where('email',$adminEmail)->first();
            if (!$existing) {
                app()->instance('currentCondominium', $condominium); // asegurar trait para User
                $user = \App\Models\User::on('tenant')->create([
                    'name' => 'Administrador',
                    'email' => $adminEmail,
                    'password' => bcrypt('1234'),
                    'active' => true,
                ]);
                // Rol super_admin si tabla roles existe
                if (\Illuminate\Support\Facades\Schema::connection('tenant')->hasTable('roles')) {
                    try {
                        $roleClass = config('permission.models.role');
                        if ($roleClass && class_exists($roleClass)) {
                            $super = $roleClass::firstOrCreate(['name'=>'super_admin','guard_name'=>'web']);
                            $user->assignRole($super);
                        }
                    } catch (\Throwable $e) {
                        $this->warn('No se pudo asignar rol super_admin: '.$e->getMessage());
                    }
                }
                $this->info('Usuario super_admin creado: '.$adminEmail.' / 1234');
            } else {
                $this->line('Usuario admin ya existía, se omite creación.');
            }
        } catch (\Throwable $e) {
            $this->error('Error creando usuario admin: '.$e->getMessage());
        }

        if ($seed) {
            $this->info('Ejecutando seeders tenant adicionales (--seed)...');
            Artisan::call('db:seed', [
                '--database' => 'tenant',
                '--class' => 'DatabaseSeeder',
                '--force' => true,
            ]);
            $this->line(Artisan::output());
        }

        $this->info('Tenant creado correctamente.');
        $this->line("URL esperada: https://{$subdomain}.tu-dominio.com (configura DNS/hosts)");
        return Command::SUCCESS;
    }

    private function generateDatabaseName(string $subdomain): string
    {
        // Sanitizar: letras, números y guiones bajos
        $base = preg_replace('/[^a-z0-9_]/', '_', $subdomain);
        $base = trim($base, '_');
        if (strlen($base) < 3) { $base .= '_tenant'; }
        return 'condo_' . substr($base, 0, 45); // límite razonable
    }

    private function databaseExists(string $dbName): bool
    {
        try {
            $result = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);
            return !empty($result);
        } catch (\Throwable $e) {
            // Si falla la consulta, asumir que no existe (o no se puede verificar)
            return false;
        }
    }
}
