<?php
namespace Tests\Unit;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvoiceLateFeeTest extends TestCase
{
    private function makeInvoice(array $attrs): Invoice
    {
        return new Invoice(array_merge([
            'status'    => 'pending',
            'total_usd' => 100,
            'due_date'  => '2026-06-30',
        ], $attrs));
    }

    public function test_sin_configuracion_de_mora_devuelve_cero(): void
    {
        Carbon::setTestNow('2026-07-15');
        $invoice = $this->makeInvoice([]);
        $this->assertSame(0.0, $invoice->computeLateFeeUsd());
    }

    public function test_antes_del_vencimiento_no_hay_mora(): void
    {
        Carbon::setTestNow('2026-06-15');
        $invoice = $this->makeInvoice([
            'late_fee_type' => 'percent',
            'late_fee_scope' => 'day',
            'late_fee_value' => 1,
        ]);
        $this->assertSame(0.0, $invoice->computeLateFeeUsd());
    }

    public function test_mora_porcentual_diaria(): void
    {
        // 5 días de atraso, 1% diario sobre 100 USD => 5.00
        Carbon::setTestNow('2026-07-05');
        $invoice = $this->makeInvoice([
            'late_fee_type' => 'percent',
            'late_fee_scope' => 'day',
            'late_fee_value' => 1,
        ]);
        $this->assertSame(5.0, $invoice->computeLateFeeUsd());
    }

    public function test_mora_semanal_cobra_solo_periodos_completos(): void
    {
        // 10 días de atraso => 1 semana completa
        Carbon::setTestNow('2026-07-10');
        $invoice = $this->makeInvoice([
            'late_fee_type' => 'percent',
            'late_fee_scope' => 'week',
            'late_fee_value' => 2,
        ]);
        $this->assertSame(2.0, $invoice->computeLateFeeUsd());

        // 6 días de atraso => 0 semanas completas
        Carbon::setTestNow('2026-07-06');
        $this->assertSame(0.0, $invoice->computeLateFeeUsd());
    }

    public function test_mora_fija_mensual(): void
    {
        // 65 días de atraso => 2 meses completos (30 días c/u) x 5 USD
        Carbon::setTestNow('2026-09-03');
        $invoice = $this->makeInvoice([
            'late_fee_type' => 'fixed',
            'late_fee_scope' => 'month',
            'late_fee_value' => 5,
        ]);
        $this->assertSame(10.0, $invoice->computeLateFeeUsd());
    }

    public function test_factura_pagada_devuelve_mora_congelada(): void
    {
        Carbon::setTestNow('2026-12-31');
        $invoice = $this->makeInvoice([
            'status' => 'paid',
            'late_fee_type' => 'percent',
            'late_fee_scope' => 'day',
            'late_fee_value' => 1,
            'late_fee_accrued_usd' => 12.34,
        ]);
        $this->assertSame(12.34, $invoice->computeLateFeeUsd());
    }
}
