@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-piggy-bank me-2"></i>Fondo de Reserva por Torre</h1>
		<p class="text-muted mb-0">Cada torre mantiene su propio fondo. Los aportes de los apartamentos de una torre no se mezclan con los de otra.</p>
	</div>
</div>

@if(session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row g-3 mb-3">
	<div class="col-md-6">
		<div class="card h-100">
			<div class="card-body">
				<div class="text-muted small text-uppercase">Total global (informativo)</div>
				<div class="d-flex justify-content-between align-items-baseline mt-1">
					<span class="fs-4 fw-bold font-monospace">{{ number_format($totalUsd, 2) }} <small class="text-muted">USD</small></span>
					<span class="fs-5 font-monospace">{{ number_format($totalVes, 2) }} <small class="text-muted">VES</small></span>
				</div>
				<div class="text-muted small mt-1">Suma de todos los fondos. Los saldos operan de forma independiente por torre.</div>
			</div>
		</div>
	</div>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Torre</th>
					<th class="text-end">% Reserva</th>
					<th class="text-end">Saldo USD</th>
					<th class="text-end">Saldo VES</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($funds as $f)
				<tr>
					<td class="fw-semibold">{{ optional($f->tower)->name ?? 'Torre #'.$f->tower_id }}</td>
					<td class="text-end font-monospace">{{ number_format((float) optional($f->tower)->reserve_percent, 2) }}%</td>
					<td class="text-end font-monospace">{{ number_format($f->balance_usd, 2) }}</td>
					<td class="text-end font-monospace">{{ number_format($f->balance_ves, 2) }}</td>
					<td class="text-end">
						<a class="btn btn-sm btn-outline-primary btn-action me-1" href="{{ route('reserve-funds.show', $f) }}"><i class="bi bi-list-ul"></i> Movimientos</a>
						<a class="btn btn-sm btn-outline-success btn-action" href="{{ route('reserve-funds.movements.create', $f) }}"><i class="bi bi-plus-circle"></i> Movimiento</a>
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="5">
						<div class="empty-state">
							<i class="bi bi-piggy-bank"></i>
							<p>No hay torres registradas</p>
						</div>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
@endsection
