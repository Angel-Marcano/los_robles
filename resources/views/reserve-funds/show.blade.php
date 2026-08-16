@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-piggy-bank me-2"></i>{{ $reserveFund->name }}</h1>
		<p class="text-muted mb-0">Torre: <strong>{{ optional($reserveFund->tower)->name ?? 'N/D' }}</strong> · % Reserva: <strong>{{ number_format((float) optional($reserveFund->tower)->reserve_percent, 2) }}%</strong></p>
	</div>
	<div class="d-flex gap-2">
		<a class="btn btn-outline-secondary btn-action" href="{{ route('reserve-funds.index') }}"><i class="bi bi-arrow-left"></i> Volver</a>
		<a class="btn btn-success btn-action" href="{{ route('reserve-funds.movements.create', $reserveFund) }}"><i class="bi bi-plus-lg"></i> Nuevo Movimiento</a>
	</div>
</div>

@if(session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row g-3 mb-3">
	<div class="col-md-6">
		<div class="card h-100"><div class="card-body">
			<div class="text-muted small text-uppercase">Saldo actual</div>
			<div class="d-flex justify-content-between align-items-baseline mt-1">
				<span class="fs-4 fw-bold font-monospace">{{ number_format($reserveFund->balance_usd, 2) }} <small class="text-muted">USD</small></span>
				<span class="fs-5 font-monospace">{{ number_format($reserveFund->balance_ves, 2) }} <small class="text-muted">VES</small></span>
			</div>
		</div></div>
	</div>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Fecha</th>
					<th>Tipo</th>
					<th>Origen</th>
					<th>Detalle</th>
					<th class="text-end">USD</th>
					<th class="text-end">VES</th>
					<th class="text-end">Tasa</th>
					<th class="text-end">Saldo USD</th>
					<th class="text-end">Saldo VES</th>
				</tr>
			</thead>
			<tbody>
				@forelse($movements as $m)
				<tr>
					<td class="text-nowrap small">{{ $m->created_at->format('d/m/Y H:i') }}</td>
					<td>
						@if($m->direction === 'income')
							<span class="badge bg-success">Ingreso</span>
						@else
							<span class="badge bg-danger">Egreso</span>
						@endif
					</td>
					<td><span class="badge bg-secondary">{{ $m->sourceLabel() }}</span></td>
					<td class="small">
						@if($m->invoice)
							<a href="{{ route('invoices.show', $m->invoice) }}">Factura {{ $m->invoice->number }}</a>
							@if($m->apartment) <span class="text-muted">· Apto {{ $m->apartment->code }}</span>@endif
							<br>
						@endif
						@if($m->notes)<span class="text-muted">{{ $m->notes }}</span>@endif
					</td>
					<td class="text-end font-monospace {{ $m->direction === 'income' ? 'text-success' : 'text-danger' }}">{{ $m->direction === 'income' ? '+' : '-' }}{{ number_format($m->amount_usd, 2) }}</td>
					<td class="text-end font-monospace {{ $m->direction === 'income' ? 'text-success' : 'text-danger' }}">{{ $m->direction === 'income' ? '+' : '-' }}{{ number_format($m->amount_ves, 2) }}</td>
					<td class="text-end font-monospace small">{{ $m->exchange_rate ? number_format($m->exchange_rate, 2) : '—' }}</td>
					<td class="text-end font-monospace">{{ number_format($m->running_usd, 2) }}</td>
					<td class="text-end font-monospace">{{ number_format($m->running_ves, 2) }}</td>
				</tr>
				@empty
				<tr>
					<td colspan="9">
						<div class="empty-state">
							<i class="bi bi-inboxes"></i>
							<p>Aún no hay movimientos en este fondo</p>
						</div>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
@endsection
