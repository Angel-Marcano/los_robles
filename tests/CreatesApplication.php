<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // En testing: la conexión tenant apunta a la misma BD que la default
        // para que RefreshDatabase cree todas las tablas en un solo lugar.
        if ($app->environment('testing')) {
            $db = config('database.connections.mysql.database');
            config(['database.connections.tenant' => config('database.connections.mysql')]);
            DB::purge('tenant');
        }

        return $app;
    }
}
