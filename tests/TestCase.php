<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // En testing, correr también las migraciones tenant en la misma BD
        if ($this->isUsingRefreshDatabase()) {
            $this->runTenantMigrations();
        }
    }

    protected function isUsingRefreshDatabase(): bool
    {
        return in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive(static::class), true);
    }

    protected function runTenantMigrations(): void
    {
        // Asegurar que la conexión tenant = mysql en testing
        config(['database.connections.tenant' => config('database.connections.mysql')]);
        DB::purge('tenant');

        // Correr migraciones tenant (pueden chocar con las del landlord
        // porque comparten BD en testing; usamos migrate:fresh --path
        // para que solo corra las tenant y ignore las que ya existen)
        $migrator = app('migrator');
        $migrator->setConnection('tenant');

        $files = $migrator->getMigrationFiles(database_path('migrations/tenant'));
        $pending = [];
        foreach ($files as $file) {
            $name = $migrator->getMigrationName($file);
            if (!$migrator->repositoryExists()) {
                $migrator->getRepository()->createRepository();
            }
            if (!in_array($name, $migrator->getRepository()->getRan())) {
                $pending[] = $file;
            }
        }

        // Correr solo las pendientes, una por una, ignorando errores
        foreach ($pending as $file) {
            try {
                $migrator->run([$file], ['pretend' => false]);
            } catch (\Throwable $e) {
                // Si falla (columna ya existe, índice duplicado), marcar como migrada
                $name = $migrator->getMigrationName($file);
                try {
                    $migrator->getRepository()->log($name, 1);
                } catch (\Throwable $e2) {
                    // Ignorar
                }
            }
        }

        // Sembrar roles
        try {
            Artisan::call('db:seed', [
                '--class'  => 'Database\\Seeders\\TenantRolesSeeder',
                '--force'  => true,
            ]);
        } catch (\Throwable $e) {
            // Ignorar si ya existen
        }
    }
}
