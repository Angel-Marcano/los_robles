@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-house-door me-2"></i>Mi Panel</h1>
	<span class="text-muted small">Hola, {{ auth()->user()->name }}</span>
</div>

{{-- Tarjetas de resumen --}}
<div class="row g-3 mb-4">
	<div class="col-md-4">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<i class="bi bi-clock text-warning me-2 fs-4"></i>
					<span class="text-muted small">Pendiente por pagar</span>
				</div>
				<h3 class="mb-0 text-warning">$ {{ number_format($totalPendiente, 2) }}</h3>
				<span class="text-muted small">USD</span>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<i class="bi bi-exclamation-triangle text-danger me-2 fs-4"></i>
					<span class="text-muted small">Moroso</span>
				</div>
				<h3 class="mb-0 {{ $totalMoroso > 0 ? 'text-danger' : 'text-success' }}">$ {{ number_format($totalMoroso, 2) }}</h3>
				<span class="text-muted small">{{ $totalMoroso > 0 ? 'Tiene facturas vencidas' : 'Al día' }}</span>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<i class="bi bi-check-circle text-success me-2 fs-4"></i>
					<span class="text-muted small">Pagado este mes</span>
				</div>
				<h3 class="mb-0 text-success">$ {{ number_format($totalPagado, 2) }}</h3>
				<span class="text-muted small">USD</span>
			</div>
		</div>
	</div>
</div>

<div class="row g-3">
	{{-- Mis apartamentos --}}
	<div class="col-lg-4">
		<div class="card border-0 shadow-sm mb-3">
			<div class="card-header bg-transparent">
				<h5 class="mb-0"><i class="bi bi-door-open me-2"></i>Mis apartamentos</h5>
			</div>
			<div class="card-body">
				@if($ownerships->isNotEmpty())
					@foreach($ownerships as $own)
						<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
							<div>
								<span class="fw-semibold">{{ $own->apartment->code ?? 'Apto #'.$own->apartment_id }}</span>
								<br><span class="text-muted small">{{ $own->apartment->tower->name ?? '—' }}</span>
							</div>
							<span class="badge bg-primary-subtle text-primary-emphasis">{{ $own->role }}</span>
						</div>
					@endforeach
				@else
					<p class="text-muted small mb-0">No tienes apartamentos asignados.</p>
				@endif
			</div>
		</div>

		{{-- Acciones rápidas --}}
		<div class="card border-0 shadow-sm">
			<div class="card-header bg-transparent">
				<h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Accesos rápidos</h5>
			</div>
			<div class="card-body d-grid gap-2">
				<a href="{{ route('invoices.index') }}" class="btn btn-outline-primary text-start">
					<i class="bi bi-receipt me-2"></i> Ver mis facturas
				</a>
				@if($pagosReportados > 0)
					<div class="alert alert-warning py-2 mb-0">
						<i class="bi bi-clock-history me-1"></i> Tienes {{ $pagosReportados }} pago(s) en revisión.
					</div>
				@endif
			</div>
		</div>
	</div>

	{{-- Mis facturas recientes --}}
	<div class="col-lg-8">
		<div class="card border-0 shadow-sm">
			<div class="card-header bg-transparent d-flex justify-content-between align-items-center">
				<h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Mis facturas recientes</h5>
				<a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">Ver todas</a>
			</div>
			<div class="card-body">
				@if($misFacturas->isNotEmpty())
					<div class="table-responsive">
						<table class="table table-sm table-hover mb-0">
							<thead>
								<tr>
									<th>Factura</th>
									<th>Período</th>
									<th>Vence</th>
									<th class="text-end">Monto</th>
									<th>Estado</th>
								</tr>
							</thead>
							<tbody>
								@foreach($misFacturas as $inv)
									<tr>
										<td>
											<a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none fw-semibold">{{ $inv->number }}</a>
										</td>
										<td>{{ $inv->period }}</td>
										<td>{{ $inv->due_date?->format('d/m/Y') ?? '—' }}</td>
										<td class="text-end">$ {{ number_format($inv->total_usd, 2) }}</td>
										<td>
											@php
												$badgeClass = [
													'paid' => 'bg-success',
													'pending' => 'bg-warning',
													'draft' => 'bg-secondary',
													'voided' => 'bg-danger',
													'reissued' => 'bg-info',
												][$inv->status] ?? 'bg-secondary';
											@endphp
											<span class="badge {{ $badgeClass }}">{{ $inv->statusLabel() }}</span>
										</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				@else
					<div class="text-center py-4">
						<i class="bi bi-inbox fs-1 text-muted"></i>
						<p class="text-muted mt-2">No tienes facturas aún.</p>
					</div>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection