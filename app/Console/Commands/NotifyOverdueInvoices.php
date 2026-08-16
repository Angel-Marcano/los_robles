<?php

namespace App\Console\Commands;

use App\Models\Condominium;
use App\Models\Invoice;
use App\Models\CurrencyRate;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyOverdueInvoices extends Command
{
    protected $signature = 'invoices:notify-overdue';
    protected $description = 'Recalcula morosidad y envía recordatorios por facturas vencidas';

    public function handle()
    {
        $this->info('Procesando facturas vencidas...');

        // Procesar landlord (condominios)
        $condominiums = Condominium::where('active', true)->get();
        foreach ($condominiums as $condominium) {
            tenancy()->initialize($condominium);
            try {
                $this->processTenantInvoices($condominium);
            } catch (\Throwable $e) {
                Log::error('NotifyOverdue failed for tenant', [
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

    protected function processTenantInvoices(Condominium $condominium)
    {
        $today = now()->startOfDay();
        $rate = CurrencyRate::where('active', true)->orderByDesc('valid_from')->first();

        // Facturas pendientes vencidas (no anuladas, no pagadas)
        $overdue = Invoice::query()
            ->where('status', 'pending')
            ->whereNull('voided_at')
            ->whereDate('due_date', '<', $today)
            ->whereDoesntHave('reissuedTo') // ignorar si ya fue reemplazada
            ->with(['apartment', 'apartment.tower', 'items'])
            ->get();

        foreach ($overdue as $invoice) {
            // Recalcular morosidad acumulada
            $lateUsd = $invoice->computeLateFeeUsd();
            $lateVes = round($lateUsd * ($rate ? $rate->rate : $invoice->exchange_rate_used), 2);

            $wasUpdated = false;
            if ((float) $invoice->late_fee_accrued_usd !== round($lateUsd, 2)) {
                $invoice->update([
                    'late_fee_accrued_usd' => $lateUsd,
                    'late_fee_accrued_ves' => $lateVes,
                ]);
                $wasUpdated = true;
            }

            // Enviar recordatorio diario simple (evitar spam: una vez por día)
            $lastReminder = $invoice->reminder_sent_at;
            if (!$lastReminder || $lastReminder->startOfDay()->lt($today)) {
                $this->sendReminder($invoice, $lateUsd, $lateVes);
                $invoice->update(['reminder_sent_at' => now()]);
            }

            if ($wasUpdated) {
                app(AuditService::class)->log('late_fee_recalculated', 'Invoice', $invoice->id, [
                    'late_fee_accrued_usd' => $lateUsd,
                    'late_fee_accrued_ves' => $lateVes,
                ]);
            }
        }
    }

    protected function sendReminder(Invoice $invoice, float $lateUsd, float $lateVes)
    {
        $ownerEmail = $invoice->owner_email;
        if (!$ownerEmail) {
            // Fallback: buscar propietario activo del apartamento
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
            Mail::to($ownerEmail)->queue(new \App\Mail\InvoiceReminderMail($invoice, $lateUsd, $lateVes));
        } catch (\Throwable $e) {
            Log::error('Invoice reminder mail failed', [
                'invoice_id' => $invoice->id,
                'email' => $ownerEmail,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
