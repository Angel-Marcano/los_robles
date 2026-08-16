<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\Condominium;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IdentifyCondominium
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        $logical = $parts[0];

        $condominium = Condominium::on('mysql')->where('subdomain', $logical)->first();
        if (!$condominium) {
            $condominium = Condominium::on('mysql')->where('subdomain', $host)->first();
        }
        if (!$condominium) {
            Log::warning('IdentifyCondominium no encontró condominio', ['host' => $host]);
            abort(404, 'Condominio no configurado para este dominio');
        }

        app()->instance('currentCondominium', $condominium);
        app()->instance('currentCondominiumId', $condominium->id);

        if (method_exists($condominium, 'hasDedicatedDatabase') && $condominium->hasDedicatedDatabase()) {
            $base = config('database.connections.mysql');
            $tenantConfig = array_merge($base, ['database' => $condominium->db_name]);
            config(['database.connections.tenant' => $tenantConfig]);
            DB::purge('tenant');
            try {
                DB::connection('tenant')->getPdo();
            } catch (\Throwable $e) {
                Log::error('Fallo conexión tenant', ['error' => $e->getMessage(), 'db' => $condominium->db_name]);
                abort(500, 'Error conexión tenant');
            }
        }

        return $next($request);
    }
}
