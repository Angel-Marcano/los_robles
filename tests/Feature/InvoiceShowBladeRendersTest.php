<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Invoice;

class InvoiceShowBladeRendersTest extends TestCase
{
	/** @test */
	public function it_renders_invoice_show_blade_without_parse_errors()
	{
		// Nota: este test no usa RefreshDatabase porque en este proyecto
		// el contexto tenant puede depender de configuración/middleware.
		// Solo valida que el Blade compile/renderice sin ParseError.
		$invoice = Invoice::query()->with(['items.expenseItem', 'items.apartment', 'children.apartment'])->first();

		if (!$invoice) {
			$this->markTestSkipped('No hay invoices en la base de datos para renderizar invoices.show.');
		}

		$html = view('invoices.show', [
			'invoice' => $invoice,
			'items' => $invoice->items,
			'isAdmin' => true,
			'isParent' => false,
			'allChildrenPaid' => false,
		])->render();

		$this->assertIsString($html);
		$this->assertStringContainsString('Factura', $html);
	}
}
