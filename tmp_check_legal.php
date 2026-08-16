<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Resolver tenant demo
$tenant = App\Models\Condominium::where('subdomain','demo')->first();
if(!$tenant){ echo "Tenant demo no encontrado\n"; exit(1); }

// Cambiar a la BD del tenant
Illuminate\Support\Facades\DB::connection('tenant')->statement('USE db_demo');

$users = App\Models\User::limit(5)->get(['id','email','accepted_privacy_at','accepted_terms_at','legal_version']);
foreach($users as $u){
    echo $u->email
        .' | privacy: '.($u->accepted_privacy_at ?? 'NULL')
        .' | terms: '.($u->accepted_terms_at ?? 'NULL')
        .' | version: '.($u->legal_version ?? 'NULL')
        ."\n";
}
