@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
	<span class="text-muted small">Período: <strong>{{ $currentPeriod }}</strong></span>
</div>

{{-- Tarjetas de resumen --}}
<div class="row g-3 mb-4">
	<div class="col-md-3">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<i class="bi bi-receipt text-primary me-2 fs-4"></i>
					<span class="text-muted small">Facturado del mes</span>
				</div>
				<h3 class="mb-0">$ {{ number_format($totalFacturado, 2) }}</h3>
				<span class="text-muted small">USD</span>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<i class="bi bi-check-circle text-success me-2 fs-4"></i>
					<span class="text-muted small">Cobrado</span>
				</div>
				<h3 class="mb-0 text-success">$ {{ number_format($totalCobrado, 2) }}</h3>
				<span class="text-muted small">USD</span>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<i class="bi bi-clock text-warning me-2 fs-4"></i>
					<span class="text-muted small">Pendiente</span>
				</div>
				<h3 class="mb-0 text-warning">$ {{ number_format($totalPendiente, 2) }}</h3>
				<span class="text-muted small">USD</span>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<i class="bi bi-exclamation-triangle text-danger me-2 fs-4"></i>
					<span class="text-muted small">Moroso</span>
				</div>
				<h3 class="mb-0 text-danger">$ {{ number_format($montoMoroso, 2) }}</h3>
				<span class="text-muted small">{{ $morosas }} facturas vencidas</span>
			</div>
		</div>
	</div>
</div>

<div class="row g-3">
	{{-- Columna izquierda --}}
	<div class="col-lg-8">
		{{-- Histórico de cobranza --}}
		<div class="card border-0 shadow-sm mb-3">
			<div class="card-header bg-transparent d-flex justify-content-between align-items-center">
				<h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Cobranza últimos 6 meses</h5>
			</div>
			<div class="card-body">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th>Período</th>
							<th class="text-end">Facturado</th>
							<th class="text-end">Cobrado</th>
							<th class="text-end">% Eficacia</th>
						</tr>
					</thead>
					<tbody>
						@foreach($historico as $h)
							@php
								$eficacia = $h['facturado'] > 0 ? round(($h['cobrado'] / $h['facturado']) * 100, 1) : 0;
							@endphp
							<tr>
								<td class="fw-semibold">{{ $h['periodo'] }}</td>
								<td class="text-end">$ {{ number_format($h['facturado'], 2) }}</td>
								<td class="text-end text-success">$ {{ number_format($h['cobrado'], 2) }}</td>
								<td class="text-end">
									<span class="badge {{ $eficacia >= 80 ? 'bg-success' : ($eficacia >= 50 ? 'bg-warning' : 'bg-danger') }}">{{ $eficacia }}%</span>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>

		{{-- Resumen por torre --}}
		<div class="card border-0 shadow-sm mb-3">
			<div class="card-header bg-transparent">
				<h5 class="mb-0"><i class="bi bi-building me-2"></i>Resumen por torre</h5>
			</div>
			<div class="card-body">
				<table class="table table-sm table-hover mb-0">
					<thead>
						<tr>
							<th>Torre</th>
							<th class="text-end">Facturado</th>
							<th class="text-end">Cobrado</th>
							<th class="text-end">Pendiente</th>
							<th class="text-end">Morosas</th>
						</tr>
					</thead>
					<tbody>
						@foreach($porTorre as $t)
							<tr>
								<td class="fw-semibold">{{ $t['nombre'] }}</td>
								<td class="text-end">$ {{ number_format($t['total'], 2) }}</td>
								<td class="text-end text-success">$ {{ number_format($t['cobrado'], 2) }}</td>
								<td class="text-end text-warning">$ {{ number_format($t['pendiente'], 2) }}</td>
								<td class="text-end">
									@if($t['morosas'] > 0)
										<span class="badge bg-danger">{{ $t['morosas'] }}</span>
									@else
										<span class="text-muted">—</span>
									@endif
								</td>
							</tr>
						@endforeach
						@if(empty($porTorre))
							<tr><td colspan="5" class="text-center text-muted py-3">Sin datos para este período</td></tr>
						@endif
					</tbody>
				</table>
			</div>
		</div>
	</div>

	{{-- Columna derecha --}}
	<div class="col-lg-4">
		{{-- Acciones rápidas --}}
		<div class="card border-0 shadow-sm mb-3">
			<div class="card-header bg-transparent">
				<h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Accesos rápidos</h5>
			</div>
			<div class="card-body d-grid gap-2">
				<a href="{{ route('invoices.create') }}" class="btn btn-outline-primary text-start">
					<i class="bi bi-plus-circle me-2"></i> Crear factura
				</a>
				<a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary text-start">
					<i class="bi bi-list me-2"></i> Ver facturas
				</a>
				@if($pagosPendientes > 0)
					<a href="{{ route('invoices.index') }}" class="btn btn-warning text-start">
						<i class="bi bi-cash-coin me-2"></i> {{ $pagosPendientes }} pagos por revisar
					</a>
				@endif
				<a href="{{ route('reports.debtorsMonthly') }}" class="btn btn-outline-info text-start">
					<i class="bi bi-exclamation-triangle me-2"></i> Reporte de morosidad
				</a>
			</div>
		</div>

		{{-- Próximos vencimientos --}}
		<div class="card border-0 shadow-sm mb-3">
			<div class="card-header bg-transparent">
				<h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Próximos vencimientos</h5>
			</div>
			<div class="card-body">
				@if($proximosVencimientos->isNotEmpty())
					@foreach($proximosVencimientos as $inv)
						<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
							<div>
								<div class="fw-semibold small">{{ $inv->number }}</div>
								<div class="text-muted small">{{ $inv->due_date->format('d/m/Y') }}</div>
							</div>
							<span class="badge bg-warning-subtle text-warning-emphasis">$ {{ number_format($inv->total_usd, 2) }}</span>
						</div>
					@endforeach
				@else
					<p class="text-muted small mb-0">No hay vencimientos próximos.</p>
				@endif
			</div>
		</div>

		{{-- Contadores --}}
		<div class="card border-0 shadow-sm">
			<div class="card-header bg-transparent">
				<h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Resumen general</h5>
			</div>
			<div class="card-body">
				<div class="d-flex justify-content-between py-1">
					<span class="text-muted">Torres</span>
					<span class="fw-semibold">{{ $totalTorres }}</span>
				</div>
				<div class="d-flex justify-content-between py-1">
					<span class="text-muted">Apartamentos</span>
					<span class="fw-semibold">{{ $totalApartamentos }}</span>
				</div>
				<div class="d-flex justify-content-between py-1">
					<span class="text-muted">Usuarios</span>
					<span class="fw-semibold">{{ $totalUsuarios }}</span>
				</div>
				@if($totalBorrador > 0)
					<div class="d-flex justify-content-between py-1">
						<span class="text-muted">En borrador</span>
						<span class="fw-semibold text-secondary">$ {{ number_format($totalBorrador, 2) }}</span>
					</div>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection