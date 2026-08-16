@extends('layouts.app')

@section('content')
	<div class="d-flex align-items-center justify-content-between page-header">
		<h1><i class="bi bi-cash-stack me-2"></i>Registrar abono — Factura #{{ $invoice->id }}</h1>
		<a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-action"><i class="bi bi-arrow-left"></i> Volver</a>
	</div>

	<div class="alert alert-info">
		<div class="d-flex justify-content-between flex-wrap gap-2">
			<div>
				<div><strong>Factura:</strong> {{ $invoice->number ?? ('#'.$invoice->id) }} — Periodo {{ $invoice->period }}</div>
				<div class="text-muted">{{ $invoice->lateFeeLabel() }} | Tasa actual: {{ number_format((float)($activeRate ?? 0), 6) }}</div>
			</div>
		</div>
		<hr class="my-2">
		<div class="row g-2">
			<div class="col-md-3"><small class="text-muted">Total USD</small><div><strong>{{ number_format((float)$invoice->total_usd, 2) }}</strong></div></div>
			<div class="col-md-3"><small class="text-muted">Mora USD</small><div><strong>{{ number_format((float)($lateUsd ?? 0), 2) }}</strong></div></div>
			<div class="col-md-3"><small class="text-muted">Pagado (aprobado) USD</small><div><strong>{{ number_format((float)($paidUsdEquivalent ?? 0), 2) }}</strong></div></div>
			<div class="col-md-3"><small class="text-muted">Saldo USD</small><div><strong>{{ number_format((float)($remainingUsd ?? 0), 2) }}</strong></div></div>
		</div>
		<div class="row g-2 mt-1">
			<div class="col-md-3"><small class="text-muted">Reportado (pendiente) USD</small><div><strong>{{ number_format((float)($reportedUsdEquivalent ?? 0), 2) }}</strong></div></div>
			<div class="col-md-9"><small class="text-muted">Sugerencia</small><div><strong>{{ number_format((float)($suggestedUsdToReport ?? 0), 2) }}</strong> USD a reportar (descontando aprobado + ya reportado)</div></div>
		</div>
		<div class="row g-2 mt-1">
			<div class="col-md-4"><small class="text-muted">Total VES (tasa actual)</small><div><strong>{{ number_format((float)($dueVesSuggested ?? 0), 2) }}</strong></div></div>
			<div class="col-md-4"><small class="text-muted">Mora VES (tasa actual)</small><div><strong>{{ number_format((float)($lateVesSuggested ?? 0), 2) }}</strong></div></div>
			<div class="col-md-4"><small class="text-muted">Saldo VES (tasa actual)</small><div><strong>{{ number_format((float)($remainingVesSuggested ?? 0), 2) }}</strong></div></div>
		</div>
		<div class="mt-2"><small class="text-muted">Puedes pagar en USD o VES. Si pagas solo en una moneda, coloca 0 en la otra.</small></div>
	</div>

	@if($errors->any())
		<div class="alert alert-danger">
			<ul class="mb-0">
				@foreach($errors->all() as $e)
					<li>{{ $e }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	<div class="card">
		<div class="card-body">
			<form method="POST" action="{{ route('payments.store', $invoice) }}" enctype="multipart/form-data">
				@csrf

				<div class="alert alert-warning py-2">
					<small>
						Si reportas un monto en VES, se convertirá a USD usando la <strong>tasa activa</strong> mostrada arriba
						y esa tasa quedará guardada en el abono para cálculos posteriores.
					</small>
				</div>

				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label">Monto USD</label>
						<input type="number" step="0.01" name="amount_usd" class="form-control @if($errors->has('amount') || $errors->has('amount_usd')) is-invalid @endif" value="{{ old('amount_usd', $suggestedUsdToReport ?? ($remainingUsd ?? null)) }}">
						@if($errors->has('amount_usd'))
							<div class="invalid-feedback">{{ $errors->first('amount_usd') }}</div>
						@elseif($errors->has('amount'))
							<div class="invalid-feedback">{{ $errors->first('amount') }}</div>
						@endif
					</div>

					<div class="col-md-6">
						<label class="form-label">Monto VES</label>
						<input type="number" step="0.01" name="amount_ves" class="form-control @if($errors->has('amount') || $errors->has('amount_ves') || $errors->has('rate')) is-invalid @endif" value="{{ old('amount_ves', $suggestedVesToReport ?? ($remainingVesSuggested ?? null)) }}">
						@if($errors->has('amount_ves'))
							<div class="invalid-feedback">{{ $errors->first('amount_ves') }}</div>
						@elseif($errors->has('rate'))
							<div class="invalid-feedback">{{ $errors->first('rate') }}</div>
						@elseif($errors->has('amount'))
							<div class="invalid-feedback">{{ $errors->first('amount') }}</div>
						@endif
					</div>

					<div class="col-12">
						<label class="form-label">Archivos (jpg/png/pdf)</label>
						<input type="file" name="files[]" multiple class="form-control">
					</div>

					<div class="col-12">
						<label class="form-label">Notas</label>
						<textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
					</div>
				</div>

				<div class="mt-3">
					<button class="btn btn-primary btn-action"><i class="bi bi-send"></i> Enviar</button>
				</div>
			</form>
		</div>
	</div>
@endsection
