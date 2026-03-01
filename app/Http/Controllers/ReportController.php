<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function debtorsMonthly(Request $request)
    {
        $year = (int) ($request->query('year') ?? now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        $months = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $invoices = Invoice::query()
            ->where('status', 'pending')
            ->whereNotNull('apartment_id')
            ->where('period', 'like', $year . '-%')
            ->with([
                'apartment.tower',
                'apartment.ownerships' => function ($q) {
                    $q->where('active', true)->with('user');
                },
                'paymentReports' => function ($q) {
                    // Compatibilidad: si hay reportes viejos sin status, los contamos como aprobados
                    $q->where(function ($inner) {
                        $inner->where('status', 'approved')->orWhereNull('status');
                    })->select(['id', 'invoice_id', 'status', 'amount_usd', 'amount_ves', 'exchange_rate_used', 'usd_equivalent']);
                },
            ])
            ->get();

        $rowsByApartment = [];

        foreach ($invoices as $invoice) {
            $period = (string) ($invoice->period ?? '');
            $month = (int) substr($period, 5, 2);
            if ($month < 1 || $month > 12) {
                continue;
            }

            $paidUsdEq = 0.0;
            foreach (($invoice->paymentReports ?? collect()) as $pr) {
                $paidUsdEq += (float) $pr->usdEquivalent();
            }

            $due = (float) $invoice->dueUsdEquivalent();
            $remaining = max(0.0, round($due - $paidUsdEq, 2));
            if ($remaining <= 0) {
                continue;
            }

            $apartment = $invoice->apartment;
            if (!$apartment) {
                continue;
            }

            $apartmentId = (int) $apartment->id;

            if (!isset($rowsByApartment[$apartmentId])) {
                $ownerName = '';
                $ownership = ($apartment->ownerships ?? collect())->first();
                if ($ownership && $ownership->user) {
                    $ownerName = (string) ($ownership->user->name ?? '');
                }

                $monthly = [];
                foreach (array_keys($months) as $m) {
                    $monthly[$m] = 0.0;
                }

                $rowsByApartment[$apartmentId] = [
                    'apartment_code' => (string) $apartment->code,
                    'tower_name' => (string) ($apartment->tower->name ?? ''),
                    'owner_name' => $ownerName,
                    'monthly' => $monthly,
                    'total' => 0.0,
                ];
            }

            $rowsByApartment[$apartmentId]['monthly'][$month] += $remaining;
            $rowsByApartment[$apartmentId]['total'] += $remaining;
        }

        $rows = array_values($rowsByApartment);
        usort($rows, function ($a, $b) {
            return ($b['total'] <=> $a['total']);
        });

        // Redondeo visual
        foreach ($rows as &$r) {
            foreach ($r['monthly'] as $m => $v) {
                $r['monthly'][$m] = round((float) $v, 2);
            }
            $r['total'] = round((float) $r['total'], 2);
        }
        unset($r);

        return view('reports.debtors_monthly', [
            'year' => $year,
            'months' => $months,
            'rows' => $rows,
        ]);
    }

    public function debtorsMonthlyPdf(Request $request)
    {
        // Reuse the same data logic
        $year = (int) ($request->query('year') ?? now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        $months = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $invoices = Invoice::query()
            ->where('status', 'pending')
            ->whereNotNull('apartment_id')
            ->where('period', 'like', $year . '-%')
            ->with([
                'apartment.tower',
                'apartment.ownerships' => function ($q) {
                    $q->where('active', true)->with('user');
                },
                'paymentReports' => function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('status', 'approved')->orWhereNull('status');
                    })->select(['id', 'invoice_id', 'status', 'amount_usd', 'amount_ves', 'exchange_rate_used', 'usd_equivalent']);
                },
            ])
            ->get();

        $rowsByApartment = [];

        foreach ($invoices as $invoice) {
            $period = (string) ($invoice->period ?? '');
            $month = (int) substr($period, 5, 2);
            if ($month < 1 || $month > 12) continue;

            $paidUsdEq = 0.0;
            foreach (($invoice->paymentReports ?? collect()) as $pr) {
                $paidUsdEq += (float) $pr->usdEquivalent();
            }

            $due = (float) $invoice->dueUsdEquivalent();
            $remaining = max(0.0, round($due - $paidUsdEq, 2));
            if ($remaining <= 0) continue;

            $apartment = $invoice->apartment;
            if (!$apartment) continue;

            $apartmentId = (int) $apartment->id;

            if (!isset($rowsByApartment[$apartmentId])) {
                $ownerName = '';
                $ownership = ($apartment->ownerships ?? collect())->first();
                if ($ownership && $ownership->user) {
                    $ownerName = (string) ($ownership->user->name ?? '');
                }

                $monthly = [];
                foreach (array_keys($months) as $m) {
                    $monthly[$m] = 0.0;
                }

                $rowsByApartment[$apartmentId] = [
                    'apartment_code' => (string) $apartment->code,
                    'tower_name' => (string) ($apartment->tower->name ?? ''),
                    'owner_name' => $ownerName,
                    'monthly' => $monthly,
                    'total' => 0.0,
                ];
            }

            $rowsByApartment[$apartmentId]['monthly'][$month] += $remaining;
            $rowsByApartment[$apartmentId]['total'] += $remaining;
        }

        $rows = array_values($rowsByApartment);
        usort($rows, fn($a, $b) => $b['total'] <=> $a['total']);

        foreach ($rows as &$r) {
            foreach ($r['monthly'] as $m => $v) {
                $r['monthly'][$m] = round((float) $v, 2);
            }
            $r['total'] = round((float) $r['total'], 2);
        }
        unset($r);

        $html = view('reports.debtors_monthly_pdf', [
            'year' => $year,
            'months' => $months,
            'rows' => $rows,
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, ['Content-Type' => 'application/pdf']);
    }
}
