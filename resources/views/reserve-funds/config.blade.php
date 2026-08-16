@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-sliders me-2"></i>Configuración de Fondos de Reserva</h1>
        <a href="{{ route('reserve-funds.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('reserve-funds.config.update') }}">
        @csrf
        @method('PATCH')

        {{-- Fondo general del condominio --}}
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-piggy-bank-fill me-2"></i>Fondo de Reserva General del Condominio</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Porcentaje aplicado sobre el total de la factura de cada apartamento (gastos comunes + reserva de torre).
                    Se acredita al fondo general del condominio cuando una factura se marca como pagada.
                </p>
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Porcentaje de reserva general (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="general_reserve_percent" class="form-control" value="{{ old('general_reserve_percent', (float)($condominium->reserve_percent ?? 0)) }}">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">0 = sin fondo de reserva general.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fondos de reserva por torre --}}
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-piggy-bank me-2"></i>Fondos de Reserva por Torre</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Torre</th>
                                <th>Activa</th>
                                <th style="width:200px">% Reserva</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($towers as $tower)
                            <tr>
                                <td>{{ $tower->name }}</td>
                                <td>
                                    @if($tower->active)
                                        <span class="badge bg-success-subtle text-success-emphasis">Activa</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">Inactiva</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="tower_reserves[{{ $tower->id }}]" value="{{ old("tower_reserves.{$tower->id}", (float)($tower->reserve_percent ?? 0)) }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if($towers->isEmpty())
                                <tr><td colspan="3" class="text-center text-muted py-3">No hay torres registradas.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-end gap-2">
            <a href="{{ route('reserve-funds.index') }}" class="btn btn-outline-secondary btn-action"><i class="bi bi-x-lg"></i> Cancelar</a>
            <button type="submit" class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar configuración</button>
        </div>
    </form>
</div>
@endsection