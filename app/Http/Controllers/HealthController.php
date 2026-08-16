<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Healthcheck simple: verifica conexión a la BD master y a la BD tenant
     * (si está resuelta por el contexto del condominio actual).
     *
     * GET /up  → 200 OK con detalle, o 503 si algo falla.
     */
    public function up(): JsonResponse
    {
        $checks = [];
        $ok = true;

        // 1. BD master
        try {
            DB::connection(config('database.default'))->select('SELECT 1');
            $checks['master'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['master'] = ['status' => 'fail', 'error' => $e->getMessage()];
            $ok = false;
        }

        // 2. BD tenant (si la conexión existe y está configurada)
        if (config('database.connections.tenant')) {
            try {
                DB::connection('tenant')->select('SELECT 1');
                $checks['tenant'] = ['status' => 'ok'];
            } catch (\Throwable $e) {
                $checks['tenant'] = ['status' => 'fail', 'error' => $e->getMessage()];
                $ok = false;
            }
        } else {
            $checks['tenant'] = ['status' => 'skip', 'reason' => 'no tenant connection configured'];
        }

        $checks['timestamp'] = now()->toIso8601String();

        return response()->json([
            'status' => $ok ? 'ok' : 'fail',
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }
}