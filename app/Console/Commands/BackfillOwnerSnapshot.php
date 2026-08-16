<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Condominium, Invoice, Ownership};
use Illuminate\Support\Facades\DB;

class BackfillOwnerSnapshot extends Command
{
    protected $signature = 'invoices:backfill-owners';
    protected $description = 'Backfill owner_name/email/document on existing child invoices from current ownership';

    public function handle(): int
    {
        $tenant = Condominium::where('active', true)->whereNotNull('db_name')->first();
        if (!$tenant) {
            $this->warn('No active tenant found.');
            return self::FAILURE;
        }

        $base = config('database.connections.mysql');
        config(['database.connections.tenant' => array_merge($base, ['database' => $tenant->db_name])]);
        DB::purge('tenant');
        DB::connection('tenant')->getPdo();
        app()->instance('currentCondominium', $tenant);

        $invoices = Invoice::whereNotNull('apartment_id')->whereNull('owner_name')->get();
        $this->info("Invoices to backfill: {$invoices->count()}");

        $bar = $this->output->createProgressBar($invoices->count());
        $updated = 0;

        foreach ($invoices as $inv) {
            $ownership = Ownership::where('apartment_id', $inv->apartment_id)
                ->where('active', true)
                ->where('role', 'owner')
                ->with('user')
                ->first();

            if ($ownership && $ownership->user) {
                $u = $ownership->user;
                $doc = ($u->document_type && $u->document_number)
                    ? $u->document_type . '-' . $u->document_number
                    : $u->document_number;

                $inv->update([
                    'owner_name'     => $u->name,
                    'owner_email'    => $u->email,
                    'owner_document' => $doc,
                ]);
                $updated++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Updated {$updated} invoices.");
        return self::SUCCESS;
    }
}
