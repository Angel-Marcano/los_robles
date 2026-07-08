<?php
namespace App\Http\Controllers;

use App\Models\{ReserveFund, ReserveFundMovement, Tower, Invoice};
use App\Services\ReserveFundService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ReserveFundController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ReserveFund::class);

        // Asegurar que cada torre activa tenga su fondo (aislado por torre).
        $towers = Tower::orderBy('name')->get();
        foreach ($towers as $tower) {
            ReserveFund::forTower($tower);
        }

        $funds = ReserveFund::with('tower')->get()->sortBy(fn($f) => optional($f->tower)->name)->values();
        $totalUsd = round((float) $funds->sum('balance_usd'), 2);
        $totalVes = round((float) $funds->sum('balance_ves'), 2);

        return view('reserve-funds.index', compact('funds', 'totalUsd', 'totalVes'));
    }

    public function show(ReserveFund $reserveFund)
    {
        $this->authorize('view', ReserveFund::class);
        $reserveFund->load('tower');

        $movements = $reserveFund->movements()
            ->with(['invoice', 'apartment', 'user'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Saldo acumulado (running balance) por movimiento.
        $runUsd = 0.0; $runVes = 0.0;
        foreach ($movements as $m) {
            $sign = $m->direction === 'income' ? 1 : -1;
            $runUsd += $sign * (float) $m->amount_usd;
            $runVes += $sign * (float) $m->amount_ves;
            $m->running_usd = round($runUsd, 2);
            $m->running_ves = round($runVes, 2);
        }

        // Mostrar del más reciente al más antiguo en la vista.
        $movements = $movements->reverse()->values();

        return view('reserve-funds.show', compact('reserveFund', 'movements'));
    }

    public function createMovement(ReserveFund $reserveFund)
    {
        $this->authorize('manage', ReserveFund::class);
        $reserveFund->load('tower');
        return view('reserve-funds.movement', compact('reserveFund'));
    }

    public function storeMovement(Request $request, ReserveFund $reserveFund, ReserveFundService $service)
    {
        $this->authorize('manage', ReserveFund::class);

        $data = $request->validate([
            'direction'     => 'required|in:income,expense',
            'amount_usd'    => 'nullable|numeric|min:0',
            'amount_ves'    => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string|max:500',
        ]);

        $usd = round((float) ($data['amount_usd'] ?? 0), 2);
        $ves = round((float) ($data['amount_ves'] ?? 0), 2);
        if ($usd <= 0 && $ves <= 0) {
            return back()->withInput()->withErrors(['amount_usd' => 'Indica un monto en USD y/o VES mayor a cero.']);
        }

        if ($data['direction'] === 'expense') {
            if ($usd > (float) $reserveFund->balance_usd + 0.005 || $ves > (float) $reserveFund->balance_ves + 0.005) {
                return back()->withInput()->withErrors(['amount_usd' => 'Fondos insuficientes para este egreso (USD/VES).']);
            }
        }

        $movement = $service->registerMovement($reserveFund, $data['direction'], [
            'source'        => 'manual',
            'amount_usd'    => $usd,
            'amount_ves'    => $ves,
            'exchange_rate' => ($data['exchange_rate'] ?? 0) > 0 ? $data['exchange_rate'] : null,
            'notes'         => $data['notes'] ?? null,
        ]);

        app(AuditService::class)->log(
            'reserve_fund_'.$data['direction'],
            'ReserveFund',
            $reserveFund->id,
            ['amount_usd' => $usd, 'amount_ves' => $ves, 'movement_id' => $movement->id]
        );

        return redirect()->route('reserve-funds.show', $reserveFund)->with('status', 'Movimiento registrado.');
    }
}
