<?php
namespace App\Models\Traits;

use Illuminate\Support\Facades\Log;

trait UsesTenantConnection
{
    protected static bool $loggedTenantConnection = false;

    public function getConnectionName()
    {
        if (app()->bound('currentCondominium') && config('database.connections.tenant')) {
            if (!self::$loggedTenantConnection) {
                $condo = app('currentCondominium');
                Log::info('UsesTenantConnection resolved tenant connection', [
                    'model' => static::class,
                    'tenant_db' => config('database.connections.tenant.database'),
                    'condominium_id' => $condo?->id,
                    'condominium_subdomain' => $condo?->subdomain,
                ]);
                self::$loggedTenantConnection = true;
            }
            return 'tenant';
        }
        if (!self::$loggedTenantConnection) {
            Log::info('UsesTenantConnection using default connection', [
                'model' => static::class,
                'default_connection' => $this->connection,
            ]);
            self::$loggedTenantConnection = true;
        }
        return $this->connection; // fallback default
    }
}
