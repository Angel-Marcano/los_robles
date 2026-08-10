<?php

namespace App\Console\Commands;

use App\Mail\MonthlyPendingInvoiceReminderMail;
use App\Models\Condominium;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyPendingInvoices extends Command
{
    protected $signature = 'invoices:notify-pending';
    protected $description = 'Envía recordatorios mensuales por facturas pendientes no vencidas al inicio de cada mes';

    public function handle()
    {
        $this->info('Enviando recordatorios de facturas pendientes...');

        $condominiums = Condominium::where('active', true)->get();
        foreach ($condominiums as $condominium) {
            tenancy()->initialize($condominium);
            try {
                $this->processTenantPendingInvoices($condominium);
            } catch (\Throwable $e) {
                Log::error('NotifyPendingInvoices failed for tenant', [
                    'tenant_id' => $condominium->id,
                    'message' => $e->getMessage(),
                ]);
                $this->error("Error en tenant {$condominium->id}: {$e->getMessage()}");
            } finally {
                tenancy()->end();
            }
        }

        $this->info('Proceso completado.');
        return 0;
    }

    protected function processTenantPendingInvoices(Condominium $condominium)
    {
        $today = now()->startOfDay();

        // Solo ejecutar el día 1 de cada mes (evitar duplicados si se programa diario)
        if ($today->day !== 1) {
            $this->warn('Este command solo envía recordatorios el día 1 de cada mes. Hoy es día ' . $today->day);
            return;
        }

        // Facturas pendientes no vencidas (no anuladas, no pagadas, vencimiento futuro o hoy)
        $pending = Invoice::query()
            ->where('status', 'pending')
            ->whereNull('voided_at')
            ->whereDate('due_date', '>=', $today)
            ->whereDoesntHave('reissuedTo')
            ->with(['apartment', 'apartment.tower', 'items'])
            ->get();

        foreach ($pending as $invoice) {
            $this->sendReminder($invoice);
        }
    }

    protected function sendReminder(Invoice $invoice)
    {
        $ownerEmail = $invoice->owner_email;

        if (!$ownerEmail) {
            $ownership = \App\Models\Ownership::where('apartment_id', $invoice->apartment_id)
                ->where('active', true)
                ->where('role', 'owner')
                ->with('user')
                ->first();
            $ownerEmail = optional($ownership->user)->email;
        }

        if (!$ownerEmail) {
            return;
        }

        try {
            Mail::to($ownerEmail)->queue(new MonthlyPendingInvoiceReminderMail($invoice));
        } catch (\Throwable $e) {
            Log::error('Monthly pending invoice reminder mail failed', [
                'invoice_id' => $invoice->id,
                'email' => $ownerEmail,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
