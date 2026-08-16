<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simular entorno de prueba
$app['config']->set('app.env', 'testing');
$app['config']->set('database.default', 'mysql');

// Buscar condominio existente
$condo = App\Models\Condominium::where('subdomain', 'condo_demo')->first();
$app->instance('currentCondominium', $condo);

// Crear torre y apartamento
$tower = App\Models\Tower::factory()->create(['condominium_id' => $condo->id]);
$apartment = App\Models\Apartment::factory()->create([
    'tower_id' => $tower->id,
    'condominium_id' => $condo->id,
]);

// Crear factura
$invoice = App\Models\Invoice::factory()->create([
    'apartment_id' => $apartment->id,
    'tower_id' => $tower->id,
    'status' => 'pending',
]);

$service = $app->make(App\Services\InvoiceVerificationService::class);
$token = $service->generateToken($invoice);

echo "Condo ID: " . $condo->id . "\n";
echo "Invoice ID: " . $invoice->id . "\n";
$reflection = new ReflectionMethod($service, 'tenantId');
$reflection->setAccessible(true);
echo "Tenant ID from service: " . $reflection->invoke($service) . "\n";
echo "Token: " . $token . "\n";

$result = $service->verifyToken($token);
echo "Result status: " . $result['status'] . "\n";

// Verificar si la factura existe en la BD actual
$found = App\Models\Invoice::withTrashed()->find($invoice->id);
echo "Invoice found: " . ($found ? 'yes' : 'no') . "\n";
echo "Invoice connection: " . $found->getConnectionName() . "\n";
