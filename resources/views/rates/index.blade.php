@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-currency-exchange me-2"></i>Tasas de Cambio</h1>
	</div>
	<a class="btn btn-primary btn-action" href="{{route('rates.create')}}"><i class="bi bi-plus-lg"></i> Nueva Tasa</a>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>Base</th>
					<th>Quote</th>
					<th class="text-end">Tasa</th>
					<th>Desde</th>
					<th>Estado</th>
				</tr>
			</thead>
			<tbody>
				@forelse($rates as $r)
				<tr>
					<td class="text-muted">{{$r->id}}</td>
					<td class="fw-semibold">{{$r->base}}</td>
					<td class="fw-semibold">{{$r->quote}}</td>
					<td class="text-end font-monospace">{{ number_format((float)$r->rate, 2) }}</td>
					<td>{{$r->valid_from}}</td>
					<td>
						@if($r->active)
							<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activa</span>
						@else
							<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactiva</span>
						@endif
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="6">
						<div class="empty-state">
							<i class="bi bi-currency-exchange"></i>
							<p>No hay tasas registradas</p>
						</div>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

@if($rates->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
	<div class="text-muted small">Mostrando {{ $rates->firstItem() }}–{{ $rates->lastItem() }} de {{ $rates->total() }}</div>
	{{ $rates->links() }}
</div>
@endif
@endsection
