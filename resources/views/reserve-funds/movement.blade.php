@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-plus-circle me-2"></i>Nuevo Movimiento</h1>
		<p class="text-muted mb-0">{{ $reserveFund->name }} · Torre {{ optional($reserveFund->tower)->name }}</p>
	</div>
	<a class="btn btn-outline-secondary btn-action" href="{{ route('reserve-funds.show', $reserveFund) }}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card">
	<div class="card-body">
		<div class="mb-3 text-muted small">
			Saldo actual: <strong class="font-monospace">{{ number_format($reserveFund->balance_usd, 2) }} USD</strong> ·
			<strong class="font-monospace">{{ number_format($reserveFund->balance_ves, 2) }} VES</strong>
		</div>
		<form method="POST" action="{{ route('reserve-funds.movements.store', $reserveFund) }}">
			@csrf
			<div class="row g-3">
				<div class="col-md-4">
					<label class="form-label">Tipo de movimiento</label>
					<select name="direction" class="form-select" required>
						<option value="income" {{ old('direction') === 'income' ? 'selected' : '' }}>Ingreso (suma al fondo)</option>
						<option value="expense" {{ old('direction') === 'expense' ? 'selected' : '' }}>Egreso (resta del fondo)</option>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label">Monto USD</label>
					<input type="number" step="0.01" min="0" name="amount_usd" class="form-control font-monospace" value="{{ old('amount_usd') }}" placeholder="0.00">
				</div>
				<div class="col-md-4">
					<label class="form-label">Monto VES</label>
					<input type="number" step="0.01" min="0" name="amount_ves" class="form-control font-monospace" value="{{ old('amount_ves') }}" placeholder="0.00">
				</div>
				<div class="col-md-4">
					<label class="form-label">Tasa aplicada (opcional)</label>
					<input type="number" step="0.000001" min="0" name="exchange_rate" class="form-control font-monospace" value="{{ old('exchange_rate') }}" placeholder="Tasa de mercado">
					<div class="form-text">Si convertiste VES a USD a la tasa flotante del mercado, indícala aquí y/o acláralo en las notas.</div>
				</div>
				<div class="col-md-8">
					<label class="form-label">Notas</label>
					<textarea name="notes" class="form-control" rows="2" maxlength="500" placeholder="Ej: cambio de VES a USD a tasa de mercado 320, uso para reparación de ascensor, etc.">{{ old('notes') }}</textarea>
				</div>
			</div>
			<div class="mt-3 d-flex gap-2">
				<button type="submit" class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Registrar</button>
				<a href="{{ route('reserve-funds.show', $reserveFund) }}" class="btn btn-outline-secondary btn-action">Cancelar</a>
			</div>
		</form>
	</div>
</div>
@endsection
