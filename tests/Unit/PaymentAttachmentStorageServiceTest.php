<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Services\PaymentAttachmentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentAttachmentStorageServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        if (app()->bound('currentCondominium')) {
            app()->forgetInstance('currentCondominium');
        }

        parent::tearDown();
    }

    public function test_guarda_adjuntos_con_ruta_por_cliente_y_factura(): void
    {
        Storage::fake('public');

        config([
            'filesystems.payment_attachments.disk' => 'public',
            'filesystems.payment_attachments.fallback_disk' => 'public',
        ]);

        app()->instance('currentCondominium', (object) [
            'subdomain' => 'los-robles',
            'name' => 'Los Robles',
        ]);

        $invoice = new Invoice();
        $invoice->id = 101;

        $service = new PaymentAttachmentStorageService();
        $path = $service->storeForInvoice(UploadedFile::fake()->image('Comprobante Final.jpg'), $invoice);

        $this->assertStringStartsWith('los-robles/facturas/factura_101/comprobantes/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_hace_fallback_a_public_si_s3_no_tiene_credenciales(): void
    {
        Storage::fake('public');

        config([
            'filesystems.payment_attachments.disk' => 's3',
            'filesystems.payment_attachments.fallback_disk' => 'public',
            'filesystems.disks.s3.key' => '',
            'filesystems.disks.s3.secret' => '',
            'filesystems.disks.s3.region' => '',
            'filesystems.disks.s3.bucket' => '',
        ]);

        app()->instance('currentCondominium', (object) [
            'subdomain' => 'los-robles',
            'name' => 'Los Robles',
        ]);

        $invoice = new Invoice();
        $invoice->id = 7;

        $service = new PaymentAttachmentStorageService();
        $path = $service->storeForInvoice(UploadedFile::fake()->create('soporte.pdf', 20), $invoice);

        $this->assertSame('public', $service->activeDisk());
        Storage::disk('public')->assertExists($path);
    }

    public function test_resuelve_url_en_disco_fallback_para_historicos_locales(): void
    {
        Storage::fake('public');

        config([
            'filesystems.payment_attachments.disk' => 's3',
            'filesystems.payment_attachments.fallback_disk' => 'public',
            'filesystems.disks.s3.key' => '',
            'filesystems.disks.s3.secret' => '',
            'filesystems.disks.s3.region' => '',
            'filesystems.disks.s3.bucket' => '',
        ]);

        $legacyPath = 'payments/archivo-historico.pdf';
        Storage::disk('public')->put($legacyPath, 'contenido');

        $service = new PaymentAttachmentStorageService();
        $url = $service->resolveUrl($legacyPath);

        $this->assertNotNull($url);
        $this->assertStringContainsString($legacyPath, (string) $url);
    }
}
