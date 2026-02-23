<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LandlordCleanupDomainTables extends Command
{
    protected $signature = 'landlord:cleanup-domain {--dry-run}';
    protected $description = 'Elimina de la BD landlord tablas de dominio que no deberían existir (towers, apartments, invoices, etc).';

    // Orden pensado: primero tablas con FK dependientes entre sí y de cuentas/invoices, etc.
    // Con FOREIGN_KEY_CHECKS = 0 el orden es menos crítico, pero dejamos explícito.
    protected array $domainTables = [
        'invoice_items', // depende de invoices, apartments, expense_items
        'payment_reports', // depende de invoices
        'account_movements', // depende de accounts
        'exchange_transactions', // depende de accounts
        'ownerships', // depende de apartments/users
        'invoices',
        'expense_items',
        'accounts',
        'currency_rates',
        'apartments',
        'towers',
        'audit_logs',
        // Tablas de auth/roles que NO deben estar en landlord
        'model_has_roles','model_has_permissions','role_has_permissions','permissions','roles','users'
    ];

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $db = config('database.connections.mysql.database');
        $this->info('BD landlord: '.$db);
        if (!$dry) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($this->domainTables as $table) {
            if (!Schema::connection('mysql')->hasTable($table)) { continue; }
            $this->warn("Tabla '{$table}' presente en landlord.");
            if ($dry) { continue; }
            try {
                Schema::connection('mysql')->drop($table);
                $this->line(" - Dropped {$table}");
            } catch (\Throwable $e) {
                $this->error(" - Error al eliminar {$table}: ".$e->getMessage());
            }
        }
        if (!$dry) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        $this->info('Proceso de limpieza completado'.($dry?' (dry-run)':''));
        return Command::SUCCESS;
    }
}
