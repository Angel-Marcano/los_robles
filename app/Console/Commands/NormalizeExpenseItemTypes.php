<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Condominium;

class NormalizeExpenseItemTypes extends Command
{
    protected $signature = 'maintenance:normalize-expense-item-types
                            {--dry-run : Only show how many rows would be updated}
                            {--all-tenants : Run for every condominium (tenant DB)}';

    protected $description = "Set expense_items.type='fixed' where it is NULL (runs on current connection / tenant DB).";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $allTenants = (bool) $this->option('all-tenants');

        if ($allTenants) {
            return $this->runForAllTenants($dryRun);
        }

        return $this->runForCurrentConnection($dryRun);
    }

    private function runForCurrentConnection(bool $dryRun): int
    {
        if (!Schema::hasTable('expense_items')) {
            $this->warn("Current connection does not have 'expense_items' table. Run this from a tenant context or use --all-tenants.");
            return self::SUCCESS;
        }

        if (!Schema::hasColumn('expense_items', 'type')) {
            $this->warn("Table 'expense_items' does not have a 'type' column. You may need to run tenant migrations.");
            return self::SUCCESS;
        }

        $query = DB::table('expense_items')->whereNull('type');
        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No expense_items with NULL type found.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("[dry-run] Would update {$count} expense_items rows (type => fixed).");
            return self::SUCCESS;
        }

        $updated = $query->update(['type' => 'fixed']);
        $this->info("Updated {$updated} expense_items rows (type => fixed).");
        return self::SUCCESS;
    }

    private function runForAllTenants(bool $dryRun): int
    {
        $this->info('Running normalization for all tenants...');

        $totalUpdated = 0;
        $totalWouldUpdate = 0;

        $tenants = Condominium::query()->get(['id', 'name', 'db_name']);
        foreach ($tenants as $tenant) {
            // Switch the tenant connection database.
            config(['database.connections.tenant.database' => $tenant->db_name]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            if (!Schema::connection('tenant')->hasTable('expense_items')) {
                $this->warn("[tenant {$tenant->id}] {$tenant->name}: no expense_items table, skipped.");
                continue;
            }

            if (!Schema::connection('tenant')->hasColumn('expense_items', 'type')) {
                $this->warn("[tenant {$tenant->id}] {$tenant->name}: expense_items has no 'type' column, skipped (needs migration).");
                continue;
            }

            $query = DB::connection('tenant')->table('expense_items')->whereNull('type');
            $count = (clone $query)->count();
            if ($count === 0) {
                $this->line("[tenant {$tenant->id}] {$tenant->name}: nothing to update.");
                continue;
            }

            if ($dryRun) {
                $this->warn("[tenant {$tenant->id}] {$tenant->name}: would update {$count} rows.");
                $totalWouldUpdate += $count;
                continue;
            }

            $updated = $query->update(['type' => 'fixed']);
            $this->info("[tenant {$tenant->id}] {$tenant->name}: updated {$updated} rows.");
            $totalUpdated += $updated;
        }

        if ($dryRun) {
            $this->info("Done. Total rows that would be updated: {$totalWouldUpdate}.");
            return self::SUCCESS;
        }

        $this->info("Done. Total updated rows: {$totalUpdated}.");
        return self::SUCCESS;
    }
}
