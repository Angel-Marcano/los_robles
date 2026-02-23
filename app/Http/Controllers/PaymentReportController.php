<?php
namespace App\Http\Controllers;

use App\Models\{Invoice, PaymentReport, CurrencyRate};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class PaymentReportController extends Controller
{
    public function create(Invoice $invoice)
    {
        $this->authorize('create', \App\Models\PaymentReport::class);

        if ($invoice->status !== 'pending') {
            return redirect()->route('invoices.show', $invoice)->withErrors(['invoice' => 'Solo puedes reportar pagos para facturas aprobadas (Pendiente).']);
        }

        // Evitar reportar pago sobre factura padre si tiene sub-facturas
        if (!$invoice->parent_id && $invoice->children()->exists()) {
            return redirect()->route('invoices.show', $invoice)->withErrors(['invoice' => 'Esta es una factura padre. Reporta el pago sobre la sub-factura del apartamento correspondiente.']);
        }

        $rate = CurrencyRate::where('active', true)->orderByDesc('valid_from')->first();
        $activeRate = $rate ? (float) $rate->rate : 0.0;

        $paidUsdEquivalent = (float) $invoice->approvedPaidUsdEquivalent();
        $reportedUsdEquivalent = (float) $invoice->reportedPaidUsdEquivalent();
        $lateUsd = (float) $invoice->computeLateFeeUsd();
        $dueUsd = (float) $invoice->dueUsdEquivalent();
        $remainingUsd = max(0.0, round($dueUsd - $paidUsdEquivalent, 2));
        $suggestedUsdToReport = max(0.0, round($dueUsd - ($paidUsdEquivalent + $reportedUsdEquivalent), 2));

        $lateVesSuggested = ($activeRate > 0) ? round($lateUsd * $activeRate, 2) : 0.0;
        $dueVesSuggested = ($activeRate > 0) ? round($dueUsd * $activeRate, 2) : 0.0;
        $remainingVesSuggested = ($activeRate > 0) ? round($remainingUsd * $activeRate, 2) : 0.0;
        $suggestedVesToReport = ($activeRate > 0) ? round($suggestedUsdToReport * $activeRate, 2) : 0.0;

        return view('payments.create', [
            'invoice' => $invoice,
            'activeRate' => $activeRate,
            'lateUsd' => $lateUsd,
            'dueUsd' => $dueUsd,
            'paidUsdEquivalent' => round($paidUsdEquivalent, 2),
            'reportedUsdEquivalent' => round($reportedUsdEquivalent, 2),
            'remainingUsd' => $remainingUsd,
            'suggestedUsdToReport' => $suggestedUsdToReport,
            'lateVesSuggested' => $lateVesSuggested,
            'dueVesSuggested' => $dueVesSuggested,
            'remainingVesSuggested' => $remainingVesSuggested,
            'suggestedVesToReport' => $suggestedVesToReport,
        ]);
    }

    public function store(Request $r, Invoice $invoice)
    {
        $this->authorize('create', \App\Models\PaymentReport::class);

        if ($invoice->status !== 'pending') {
            return redirect()->route('invoices.show', $invoice)->withErrors(['invoice' => 'Solo puedes reportar pagos para facturas aprobadas (Pendiente).']);
        }

        if (!$invoice->parent_id && $invoice->children()->exists()) {
            return redirect()->route('invoices.show', $invoice)->withErrors(['invoice' => 'Esta es una factura padre. Reporta el pago sobre la sub-factura del apartamento correspondiente.']);
        }

        $data = $r->validate([
            'amount_usd' => 'nullable|numeric|min:0',
            'amount_ves' => 'nullable|numeric|min:0',
            'files.*'    => 'file|mimes:jpg,jpeg,png,pdf|max:4096',
            'notes'      => 'nullable|string'
        ]);

        $amountUsd = (float) ($data['amount_usd'] ?? 0);
        $amountVes = (float) ($data['amount_ves'] ?? 0);
        if ($amountUsd <= 0 && $amountVes <= 0) {
            return back()->withErrors(['amount' => 'Debes indicar un monto en USD o VES.'])->withInput();
        }

        $rate = CurrencyRate::where('active', true)->orderByDesc('valid_from')->first();
        if ($amountVes > 0 && !$rate) {
            return back()->withErrors(['rate' => 'No hay una tasa activa para registrar pagos en VES.'])->withInput();
        }

        // Validar que el abono no exceda el saldo pendiente (total + mora - aprobado - ya reportado)
        $approvedUsdEquivalent = (float) $invoice->approvedPaidUsdEquivalent();
        $reportedUsdEquivalent = (float) $invoice->reportedPaidUsdEquivalent();
        $dueUsd = (float) $invoice->dueUsdEquivalent();
        $maxAdditionalUsdEq = max(0.0, $dueUsd - ($approvedUsdEquivalent + $reportedUsdEquivalent));

        $rateUsed = $rate ? (float) $rate->rate : 0.0;
        $thisReportUsdEq = $amountUsd + (($rateUsed > 0) ? ($amountVes / $rateUsed) : 0.0);

        // Tolerancia pequeña por redondeos
        if ($thisReportUsdEq - $maxAdditionalUsdEq > 0.005) {
            $maxUsdFmt = number_format(max(0.0, round($maxAdditionalUsdEq, 2)), 2);
            $maxVesFmt = $rateUsed > 0 ? number_format(max(0.0, round($maxAdditionalUsdEq * $rateUsed, 2)), 2) : null;
            $msg = 'El monto reportado excede el saldo pendiente. Máximo a reportar: '.$maxUsdFmt.' USD';
            if ($maxVesFmt !== null) {
                $msg .= ' (≈ '.$maxVesFmt.' VES)';
            }
            $msg .= '.';
            return back()->withErrors(['amount' => $msg])->withInput();
        }

        $paths = [];
        if ($r->hasFile('files')) {
            foreach ($r->file('files') as $file) {
                $paths[] = $file->store('payments', 'public');
            }
        }

        $report = PaymentReport::create([
            'invoice_id'          => $invoice->id,
            'user_id'             => auth()->id(),
            'amount_usd'          => $amountUsd,
            'amount_ves'          => $amountVes,
            // Reemplaza operador nullsafe (PHP8) por ternario para compatibilidad PHP7.4
            'exchange_rate_used'  => $rate ? $rate->rate : 0,
            'usd_equivalent'      => round((float) $thisReportUsdEq, 2),
            'status'              => 'reported',
            'files'               => $paths,
            'notes'               => $data['notes'] ?? null,
        ]);

        app(AuditService::class)->log('payment_report_created', 'PaymentReport', $report->id, $report->toArray());
        return redirect()->route('invoices.show', $invoice);
    }

    public function review(PaymentReport $paymentReport)
    {
        return view('payments.review', compact('paymentReport'));
    }

    public function approve(PaymentReport $paymentReport)
    {
        $this->authorize('approve',$paymentReport);
        $paymentReport->load('invoice');
        $invoice = $paymentReport->invoice;

        if (!$invoice) {
            return back()->withErrors(['paymentReport' => 'No se encontró la factura asociada al reporte.']);
        }

        if ($paymentReport->status !== 'reported') {
            return back()->withErrors(['paymentReport' => 'Este reporte ya fue procesado.']);
        }

        if (((float) ($paymentReport->amount_ves ?? 0)) > 0 && ((float) ($paymentReport->exchange_rate_used ?? 0)) <= 0) {
            return back()->withErrors(['paymentReport' => 'Este abono tiene monto en VES pero no tiene tasa registrada. No se puede aprobar; recházalo y registra uno nuevo con tasa activa.']);
        }

        if ($invoice->status === 'paid') {
            return back()->withErrors(['invoice' => 'Esta factura ya está marcada como pagada.']);
        }

        // Validación de consistencia: no aprobar si este abono excede el saldo pendiente (total + mora - aprobado).
        $dueUsd = (float) $invoice->dueUsdEquivalent();
        $approvedUsdEquivalent = (float) $invoice->approvedPaidUsdEquivalent();
        $maxAdditionalUsdEq = max(0.0, $dueUsd - $approvedUsdEquivalent);
        $thisReportUsdEq = (float) $paymentReport->usdEquivalent();
        if ($thisReportUsdEq - $maxAdditionalUsdEq > 0.005) {
            $maxUsdFmt = number_format(max(0.0, round($maxAdditionalUsdEq, 2)), 2);
            return back()->withErrors([
                'paymentReport' => 'Este abono excede el saldo pendiente. Máximo aprobable ahora: '.$maxUsdFmt.' USD. Puedes rechazar este abono o aprobar primero otros según corresponda.',
            ]);
        }

        // Evitar pagar factura padre con sub-facturas: el pago debe registrarse contra la sub-factura del apartamento.
        if(!$invoice->parent_id && $invoice->children()->exists()){
            $hasPendingChildren = $invoice->children()->where('status','!=','paid')->exists();
            if($hasPendingChildren){
                return back()->withErrors(['invoice' => 'Esta es una factura padre con sub-facturas pendientes. Debes aprobar el pago sobre la sub-factura del apartamento correspondiente.']);
            }
        }

        DB::transaction(function () use ($paymentReport, $invoice) {
            // Guardar usd_equivalent si viene nulo (backfill de registros antiguos)
            $updates = ['status' => 'approved'];
            if ($paymentReport->usd_equivalent === null) {
                $updates['usd_equivalent'] = round((float) $paymentReport->usdEquivalent(), 2);
            }
            $paymentReport->update($updates);

            $paidUsdEquivalent = (float) $invoice->approvedPaidUsdEquivalent();
            $lateUsd = (float) $invoice->computeLateFeeUsd();
            $dueUsd = (float) $invoice->dueUsdEquivalent();

            // Considerar pagada si el acumulado aprobado cubre el total + mora (USD equivalente)
            if ($paidUsdEquivalent + 0.005 >= $dueUsd) {
                $paidRate = (float) ($paymentReport->exchange_rate_used ?? 0);
                $lateVes = round($lateUsd * $paidRate, 2);
                $invoice->update([
                    'status'               => 'paid',
                    'paid_at'              => now(),
                    'paid_exchange_rate'   => $paidRate,
                    'late_fee_accrued_usd' => $lateUsd,
                    'late_fee_accrued_ves' => $lateVes,
                ]);

                // Si es una sub-factura, verificar si todas las hermanas están pagadas para marcar al padre.
                if ($invoice->parent_id) {
                    $parent = Invoice::find($invoice->parent_id);
                    if ($parent && $parent->children()->where('status','!=','paid')->count()===0) {
                        $parentLateUsd = (float) $parent->computeLateFeeUsd();
                        $parentLateVes = round($parentLateUsd * $paidRate, 2);
                        $parent->update([
                            'status'               => 'paid',
                            'paid_at'              => now(),
                            'paid_exchange_rate'   => $paidRate,
                            'late_fee_accrued_usd' => $parentLateUsd,
                            'late_fee_accrued_ves' => $parentLateVes,
                        ]);
                    }
                }
            }
        });

        app(AuditService::class)->log('payment_report_approved', 'PaymentReport', $paymentReport->id, ['status' => 'approved']);
        return redirect()->route('invoices.show', $invoice);
    }

    public function reject(PaymentReport $paymentReport)
    {
        $this->authorize('reject',$paymentReport);
        $paymentReport->update(['status' => 'rejected']);
        app(AuditService::class)->log('payment_report_rejected', 'PaymentReport', $paymentReport->id, ['status' => 'rejected']);
        return back();
    }
}
