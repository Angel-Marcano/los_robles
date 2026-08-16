<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentReport;
use App\Models\Tower;
use App\Models\Apartment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isOwner = $user->hasRole('owner') || $user->hasRole('co_owner') || $user->hasRole('tenant');

        if ($isOwner && !$user->hasRole('super_admin') && !$user->hasRole('condo_admin') && !$user->hasRole('tower_admin')) {
            return $this->ownerDashboard($user);
        }

        return $this->adminDashboard($user);
    }

    private function adminDashboard($user)
    {
        // Facturación del mes actual
        $currentPeriod = now()->format('Y-m');
        $monthInvoices = Invoice::where('period', $currentPeriod)
            ->whereNull('parent_id')
            ->whereNotIn('status', ['voided', 'reissued'])
            ->get();

        $totalFacturado = $monthInvoices->sum('total_usd');
        $totalCobrado = $monthInvoices->where('status', 'paid')->sum('total_usd');
        $totalPendiente = $monthInvoices->where('status', 'pending')->sum('total_usd');
        $totalBorrador = $monthInvoices->where('status', 'draft')->sum('total_usd');

        // Morosidad: facturas vencidas no pagadas
        $morosas = Invoice::where('status', 'pending')
            ->where('due_date', '<', now())
            ->whereNotNull('parent_id')
            ->count();
        $montoMoroso = Invoice::where('status', 'pending')
            ->where('due_date', '<', now())
            ->whereNotNull('parent_id')
            ->sum('total_usd');

        // Pagos reportados pendientes de revisión
        $pagosPendientes = PaymentReport::where('status', 'reported')->count();

        // Próximos vencimientos (7 días)
        $proximosVencimientos = Invoice::where('status', 'pending')
            ->whereNotNull('parent_id')
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->orderBy('due_date')
            ->limit(10)
            ->get(['id', 'number', 'apartment_id', 'due_date', 'total_usd']);

        // Resumen por torre
        $torres = Tower::withCount(['apartments'])->orderBy('name')->get();
        $porTorre = [];
        foreach ($torres as $torre) {
            $facturasTorre = Invoice::where('tower_id', $torre->id)
                ->where('period', $currentPeriod)
                ->whereNotNull('parent_id')
                ->whereNotIn('status', ['voided', 'reissued'])
                ->get();
            $porTorre[] = [
                'nombre' => $torre->name,
                'total' => $facturasTorre->sum('total_usd'),
                'cobrado' => $facturasTorre->where('status', 'paid')->sum('total_usd'),
                'pendiente' => $facturasTorre->where('status', 'pending')->sum('total_usd'),
                'morosas' => Invoice::where('tower_id', $torre->id)
                    ->where('status', 'pending')
                    ->where('due_date', '<', now())
                    ->whereNotNull('parent_id')
                    ->count(),
            ];
        }

        // Contadores generales
        $totalTorres = Tower::count();
        $totalApartamentos = Apartment::count();
        $totalUsuarios = \App\Models\User::count();

        // Histórico de cobranza (últimos 6 meses)
        $historico = [];
        for ($i = 5; $i >= 0; $i--) {
            $period = now()->subMonths($i)->format('Y-m');
            $facturas = Invoice::where('period', $period)
                ->whereNotNull('parent_id')
                ->whereNotIn('status', ['voided', 'reissued'])
                ->get();
            $historico[] = [
                'periodo' => $period,
                'facturado' => $facturas->sum('total_usd'),
                'cobrado' => $facturas->where('status', 'paid')->sum('total_usd'),
            ];
        }

        return view('dashboard.admin', compact(
            'totalFacturado', 'totalCobrado', 'totalPendiente', 'totalBorrador',
            'morosas', 'montoMoroso', 'pagosPendientes',
            'proximosVencimientos', 'porTorre',
            'totalTorres', 'totalApartamentos', 'totalUsuarios',
            'historico', 'currentPeriod'
        ));
    }

    private function ownerDashboard($user)
    {
        // Obtener apartamentos del usuario
        $ownerships = $user->ownerships()->with('apartment.tower')->get();
        $apartmentIds = $ownerships->pluck('apartment_id')->toArray();

        // Mis facturas
        $misFacturas = Invoice::whereIn('apartment_id', $apartmentIds)
            ->whereNotNull('parent_id')
            ->whereNotIn('status', ['voided', 'reissued'])
            ->orderByDesc('period')
            ->limit(10)
            ->get();

        $totalPendiente = Invoice::whereIn('apartment_id', $apartmentIds)
            ->where('status', 'pending')
            ->whereNotNull('parent_id')
            ->sum('total_usd');

        $totalMoroso = Invoice::whereIn('apartment_id', $apartmentIds)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->whereNotNull('parent_id')
            ->sum('total_usd');

        $totalPagado = Invoice::whereIn('apartment_id', $apartmentIds)
            ->where('status', 'paid')
            ->whereNotNull('parent_id')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total_usd');

        // Pagos reportados pendientes
        $pagosReportados = PaymentReport::where('user_id', $user->id)
            ->where('status', 'reported')
            ->count();

        return view('dashboard.owner', compact(
            'ownerships', 'misFacturas',
            'totalPendiente', 'totalMoroso', 'totalPagado',
            'pagosReportados'
        ));
    }
}