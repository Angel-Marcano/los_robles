<?php

namespace App\Http\Controllers;

use App\Models\Tower;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ReserveConfigController extends Controller
{
    public function edit()
    {
        $this->authorize('manage', \App\Models\ReserveFund::class);

        $condominium = app()->bound('currentCondominium') ? app('currentCondominium') : null;
        $towers = Tower::orderBy('name')->get();

        return view('reserve-funds.config', compact('condominium', 'towers'));
    }

    public function update(Request $request, AuditService $audit)
    {
        $this->authorize('manage', \App\Models\ReserveFund::class);

        $data = $request->validate([
            'general_reserve_percent' => 'required|numeric|min:0|max:100',
            'tower_reserves'           => 'nullable|array',
            'tower_reserves.*'         => 'numeric|min:0|max:100',
        ]);

        $condominium = app()->bound('currentCondominium') ? app('currentCondominium') : null;

        // Guardar % general del condominio
        if ($condominium) {
            $oldGeneral = (float) $condominium->reserve_percent;
            $condominium->update(['reserve_percent' => (float) $data['general_reserve_percent']]);
            if ($oldGeneral !== (float) $data['general_reserve_percent']) {
                $audit->log('reserve_config_general_updated', 'Condominium', $condominium->id, [
                    'old' => $oldGeneral,
                    'new' => (float) $data['general_reserve_percent'],
                ]);
            }
        }

        // Guardar % por torre
        $towerReserves = $data['tower_reserves'] ?? [];
        foreach ($towerReserves as $towerId => $percent) {
            $tower = Tower::find((int) $towerId);
            if (!$tower) { continue; }
            $old = (float) $tower->reserve_percent;
            $new = (float) $percent;
            if ($old !== $new) {
                $tower->update(['reserve_percent' => $new]);
                $audit->log('reserve_config_tower_updated', 'Tower', $tower->id, [
                    'old' => $old,
                    'new' => $new,
                ]);
            }
        }

        return redirect()->route('reserve-funds.config.edit')->with('status', 'Configuración de reservas guardada.');
    }
}