<?php
namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\CurrencyRate;
use App\Models\ExpenseItem;
use App\Models\Invoice;
use App\Models\Tower;
use App\Services\BillingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Prueba la matemática de facturación contra el esquema tenant en sqlite :memory:.
 */
class BillingServiceTest extends TestCase
{
    protected BillingService $billing;
    protected string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();
        // Algunas migraciones tenant usan Schema::connection('tenant') hardcodeado,
        // así que apuntamos 'sqlite' (default) y 'tenant' al mismo archivo sqlite temporal.
        $this->dbPath = storage_path('framework/testing/billing_' . getmypid() . '.sqlite');
        if (!is_dir(dirname($this->dbPath))) {
            mkdir(dirname($this->dbPath), 0777, true);
        }
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
        touch($this->dbPath);
        $sqliteConfig = [
            'driver' => 'sqlite',
            'database' => $this->dbPath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ];
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => $sqliteConfig,
            'database.connections.tenant' => $sqliteConfig,
        ]);
        DB::purge('sqlite');
        DB::purge('tenant');
        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
        $this->billing = app(BillingService::class);
    }

    protected function tearDown(): void
    {
        $path = $this->dbPath;
        DB::purge('sqlite');
        DB::purge('tenant');
        parent::tearDown();
        @unlink($path);
    }

    private function seedBase(array $aliquots = [60, 40]): array
    {
        $tower = Tower::create(['name' => 'Torre A', 'active' => true]);
        $apartments = [];
        foreach ($aliquots as $i => $pct) {
            $apartments[] = Apartment::create([
                'tower_id' => $tower->id,
                'code' => 'A-0' . ($i + 1),
                'active' => true,
                'aliquot_percent' => $pct,
            ]);
        }
        $expense = ExpenseItem::create(['name' => 'Aseo', 'type' => 'fixed', 'active' => true]);
        CurrencyRate::create([
            'base' => 'USD', 'quote' => 'VES', 'rate' => 100,
            'valid_from' => now(), 'active' => true,
        ]);
        return [$tower, $apartments, $expense];
    }

    public function test_distribucion_por_alicuota_reparte_proporcionalmente(): void
    {
        [$tower, $apartments, $expense] = $this->seedBase([60, 40]);
        $aptIds = array_map(fn($a) => $a->id, $apartments);

        $invoice = $this->billing->generateInvoice('2026-07', [$expense->id], $aptIds, [], $tower->id, [
            ['expense_item_id' => $expense->id, 'amount' => 100, 'quantity' => 1, 'distribution' => 'aliquota'],
        ]);

        $items = $invoice->items()->orderBy('apartment_id')->get();
        $this->assertCount(2, $items);
        $this->assertEquals(60.00, (float) $items[0]->subtotal_usd);
        $this->assertEquals(40.00, (float) $items[1]->subtotal_usd);
        $this->assertEquals(100.00, (float) $invoice->total_usd);
        $this->assertEquals(10000.00, (float) $invoice->total_ves);
        $this->assertTrue((bool) $items[0]->distributed);
    }

    public function test_distribucion_igual_cada_apartamento_paga_completo(): void
    {
        [$tower, $apartments, $expense] = $this->seedBase([60, 40]);
        $aptIds = array_map(fn($a) => $a->id, $apartments);

        $invoice = $this->billing->generateInvoice('2026-07', [$expense->id], $aptIds, [], $tower->id, [
            ['expense_item_id' => $expense->id, 'amount' => 10, 'quantity' => 2, 'distribution' => 'equal'],
        ]);

        $items = $invoice->items()->get();
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertEquals(20.00, (float) $item->subtotal_usd); // 10 x 2 por apartamento
            $this->assertEquals(10.00, (float) $item->base_amount_usd);
        }
        $this->assertEquals(40.00, (float) $invoice->total_usd);
    }

    public function test_redondeo_por_alicuota_no_pierde_centavos(): void
    {
        [$tower, $apartments, $expense] = $this->seedBase([33.33, 33.33, 33.34]);
        $aptIds = array_map(fn($a) => $a->id, $apartments);

        $invoice = $this->billing->generateInvoice('2026-07', [$expense->id], $aptIds, [], $tower->id, [
            ['expense_item_id' => $expense->id, 'amount' => 100, 'quantity' => 1, 'distribution' => 'aliquota'],
        ]);

        $sumItems = (float) $invoice->items()->sum('subtotal_usd');
        $this->assertEquals($sumItems, (float) $invoice->total_usd);
        $this->assertEqualsWithDelta(100.00, $sumItems, 0.02);
    }

    public function test_regenerar_factura_reemplaza_items_y_conserva_monto_base(): void
    {
        [$tower, $apartments, $expense] = $this->seedBase([60, 40]);
        $aptIds = array_map(fn($a) => $a->id, $apartments);

        $invoice = $this->billing->generateInvoice('2026-07', [$expense->id], $aptIds, [], $tower->id, [
            ['expense_item_id' => $expense->id, 'amount' => 100, 'quantity' => 1, 'distribution' => 'aliquota'],
        ]);

        // Regresión: 1.01 debe guardarse tal cual (sin deriva a 1.11)
        $this->billing->regenerateInvoice($invoice, '2026-08', [$expense->id], $aptIds, [], $tower->id, [
            ['expense_item_id' => $expense->id, 'amount' => 1.01, 'quantity' => 1, 'distribution' => 'equal'],
        ]);
        $invoice->refresh();

        $items = $invoice->items()->get();
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertEquals(1.01, (float) $item->base_amount_usd);
            $this->assertEquals(1.01, (float) $item->subtotal_usd);
        }
        $this->assertEquals('2026-08', $invoice->period);
        $this->assertEquals('2026-08-31', $invoice->due_date->format('Y-m-d'));
        $this->assertEquals(2.02, (float) $invoice->total_usd);
    }

    public function test_regenerar_sin_items_deja_borrador_vacio(): void
    {
        [$tower, $apartments, $expense] = $this->seedBase();
        $aptIds = array_map(fn($a) => $a->id, $apartments);

        $invoice = $this->billing->generateInvoice('2026-07', [$expense->id], $aptIds, [], $tower->id, [
            ['expense_item_id' => $expense->id, 'amount' => 50, 'quantity' => 1, 'distribution' => 'equal'],
        ]);

        $this->billing->regenerateInvoice($invoice, '2026-07', [], [], [], $tower->id, []);
        $invoice->refresh();

        $this->assertSame(0, $invoice->items()->count());
        $this->assertEquals(0.00, (float) $invoice->total_usd);
        $this->assertEquals(0.00, (float) $invoice->total_ves);
        $this->assertSame('draft', $invoice->status);
    }
}
