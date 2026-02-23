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
        // Siempre tomamos la primera parte antes del primer punto como subdomain lógico
        $logical = $parts[0];
        $condominium = Condominium::where('subdomain', $logical)->first();
        $attempts = ['logical-first-part:'.$logical];
        if (!$condominium) {
            $condominium = Condominium::where('subdomain', $host)->first();
            $attempts[] = 'full-host:'.$host;
        }
        if (!$condominium) {
            Log::warning('IdentifyCondominium no encontró condominio', ['host'=>$host,'attempts'=>$attempts]);
            abort(404, 'Condominio no configurado para este dominio');
        }

        Log::info('IdentifyCondominium resolved', [
            'host' => $host,
            'attempts' => $attempts,
            'resolved_subdomain' => $condominium->subdomain,
            'resolved_id' => $condominium->id,
        ]);

        app()->instance('currentCondominium', $condominium);
        if ($condominium) {
            app()->instance('currentCondominiumId', $condominium->id);
            // Configurar conexión tenant si tiene db dedicada
            if (method_exists($condominium, 'hasDedicatedDatabase') && $condominium->hasDedicatedDatabase()) {
                $base = config('database.connections.mysql');
                $tenantConfig = array_merge($base, [ 'database' => $condominium->db_name ]);
                config(['database.connections.tenant' => $tenantConfig]);
                // Purge y reconectar para asegurar nueva DB en el ciclo actual
                DB::purge('tenant');
                try {
                    DB::connection('tenant')->getPdo();
                } catch (\Throwable $e) {
                    Log::error('Fallo conexión tenant', ['error'=>$e->getMessage(),'db'=>$condominium->db_name]);
                    abort(500,'Error conexión tenant');
                }
                Log::info('Tenant connection configured', [ 'db_name' => $condominium->db_name ]);
            }
        }
        return $next($request);
    }
}
