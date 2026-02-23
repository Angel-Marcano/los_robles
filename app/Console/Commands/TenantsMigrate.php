<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Condominium;
use Illuminate\Support\Facades\DB;

class TenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate {--fresh} {--seed} {--tenant=* : IDs específicos de condominios}';
    protected $description = 'Ejecuta migraciones (y opcionalmente seeders) en cada base de datos de condominio (tenant).';

    public function handle(): int
    {
        $ids = $this->option('tenant');
        $query = Condominium::query()->whereNotNull('db_name')->where('active', true);
        if ($ids && count($ids)) { $query->whereIn('id', $ids); }
        $tenants = $query->get();
        if ($tenants->isEmpty()) { $this->warn('No hay condominios activos con db_name definida.'); return Command::SUCCESS; }

        foreach ($tenants as $tenant) {
            $this->line("===> Migrando tenant #{$tenant->id} ({$tenant->name}) BD: {$tenant->db_name}");
            $base = config('database.connections.mysql');
            $tenantConfig = array_merge($base, ['database' => $tenant->db_name]);
            config(['database.connections.tenant' => $tenantConfig]);
            DB::purge('tenant');
            try { DB::connection('tenant')->getPdo(); } catch (\Throwable $e) { $this->error("No se puede conectar a {$tenant->db_name}: {$e->getMessage()}"); continue; }

            $path = database_path('migrations/tenant');
            if (!is_dir($path)) { $this->error('Directorio tenant migrations no existe'); return Command::FAILURE; }

            if ($this->option('fresh')) {
                Artisan::call('migrate:fresh', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
                $this->info(Artisan::output());
            } else {
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
                $this->info(Artisan::output());
            }
            if ($this->option('seed')) {
                Artisan::call('db:seed', [
                    '--database' => 'tenant',
                    '--class' => 'DatabaseSeeder',
                    '--force' => true,
                ]);
                $this->info(Artisan::output());
            }
        }
        $this->info('Migraciones tenant finalizadas.');
        return Command::SUCCESS;
    }
}
