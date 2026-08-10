<?php

namespace Tests\Feature;

use App\Models\Condominium;
use App\Models\Invoice;
use App\Models\Tower;
use App\Models\Apartment;
use App\Services\InvoiceVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceQrVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El middleware IdentifyCondominium requiere un condominio para el host de prueba.
        $condo = Condominium::factory()->create([
            'name' => 'Condominio Demo',
            'subdomain' => 'condo_demo',
        ]);

        // Asegurar que el container tenga el condominio actual, ya que en tests
        // el middleware global no siempre lo vuelve a instanciar entre requests.
        app()->instance('currentCondominium', $condo);
    }

    private function createInvoice(array $overrides = []): Invoice
    {
        $condo = app('currentCondominium');
        $tower = Tower::factory()->create(['condominium_id' => $condo->id]);
        $apartment = Apartment::factory()->create([
            'tower_id' => $tower->id,
            'condominium_id' => $condo->id,
        ]);

        return Invoice::factory()->create(array_merge([
            'apartment_id' => $apartment->id,
            'tower_id' => $tower->id,
            'condominium_id' => $condo->id,
            'status' => 'pending',
            'total_usd' => 100.00,
            'total_ves' => 4000.00,
            'exchange_rate_used' => 40.00,
            'due_date' => now()->addDays(10),
            'owner_name' => 'Propietario Demo',
            'owner_email' => 'owner@example.com',
        ], $overrides));
    }

    /** @test */
    public function qr_token_verifies_a_valid_invoice()
    {
        $invoice = $this->createInvoice();
        $service = app(InvoiceVerificationService::class);
        $token = $service->generateToken($invoice);

        $result = $service->verifyToken($token);

        $this->assertEquals('valida', $result['status']);
        $this->assertInstanceOf(Invoice::class, $result['invoice']);
        $this->assertEquals($invoice->id, $result['invoice']->id);
    }

    /** @test */
    public function qr_token_detects_altered_signature()
    {
        $invoice = $this->createInvoice();
        $service = app(InvoiceVerificationService::class);
        $service->ensureSignature($invoice);

        // Alterar un dato clave de la factura (monto) sin regenerar firma.
        $invoice->update(['total_usd' => 999.00]);

        $token = $service->generateToken($invoice);
        $result = $service->verifyToken($token);

        $this->assertEquals('anulada', $result['status']);
    }

    /** @test */
    public function qr_token_detects_voided_invoice()
    {
        $invoice = $this->createInvoice();
        $service = app(InvoiceVerificationService::class);
        $service->ensureSignature($invoice);

        $invoice->update([
            'status' => 'voided',
            'voided_at' => now(),
        ]);

        $token = $service->generateToken($invoice);
        $result = $service->verifyToken($token);

        $this->assertEquals('anulada', $result['status']);
    }

    /** @test */
    public function qr_token_rejects_invoice_from_another_condominium()
    {
        $invoice = $this->createInvoice();
        $service = app(InvoiceVerificationService::class);
        $token = $service->generateToken($invoice);

        // Simular otro condominio cambiando el tenant resuelto en el container.
        $otherCondo = Condominium::factory()->create([
            'name' => 'Otro Condominio',
            'subdomain' => 'otro_condo',
        ]);

        app()->instance('currentCondominium', $otherCondo);

        $result = $service->verifyToken($token);

        $this->assertEquals('no-existe', $result['status']);
    }

    /** @test */
    public function verify_endpoint_renders_valid_status()
    {
        $invoice = $this->createInvoice();
        $service = app(InvoiceVerificationService::class);
        $token = $service->generateToken($invoice);

        $this->get(route('verify.invoice.short', ['token' => $token]))
            ->assertStatus(200)
            ->assertSee('válida', false);
    }

    /** @test */
    public function verify_endpoint_renders_invalid_status_for_tampered_token()
    {
        $this->get(route('verify.invoice.short', ['token' => 'v1.tampered.token']))
            ->assertStatus(200)
            ->assertSee('no existe', false);
    }
}
