@extends('layouts.app')

@section('content')
	<div class="d-flex align-items-center justify-content-between mb-3">
		<h1 class="h4 mb-0">Revisar Pago #{{ $paymentReport->id }}</h1>
		<a href="{{ route('invoices.show', $paymentReport->invoice_id) }}" class="btn btn-outline-secondary btn-sm">Volver</a>
	</div>

	<div class="card">
		<div class="card-body">
			<dl class="row mb-0">
				<dt class="col-sm-3">Factura</dt>
				<dd class="col-sm-9">{{ $paymentReport->invoice_id }}</dd>

				<dt class="col-sm-3">Estado</dt>
				<dd class="col-sm-9">{{ $paymentReport->status }}</dd>

				<dt class="col-sm-3">Monto USD</dt>
				<dd class="col-sm-9">{{ number_format((float)$paymentReport->amount_usd, 2) }}</dd>

				<dt class="col-sm-3">Monto VES</dt>
				<dd class="col-sm-9">{{ number_format((float)$paymentReport->amount_ves, 2) }}</dd>

				<dt class="col-sm-3">Tasa usada</dt>
				<dd class="col-sm-9">{{ $paymentReport->exchange_rate_used }}</dd>

				<dt class="col-sm-3">Notas</dt>
				<dd class="col-sm-9">{{ $paymentReport->notes }}</dd>
			</dl>

			@if(!empty($paymentReport->files))
				<hr>
				<h2 class="h6">Archivos</h2>
				<ul class="mb-0">
					@foreach($paymentReport->files as $f)
						<li>
							<a href="{{ Storage::disk('public')->url($f) }}" target="_blank" rel="noopener">Archivo</a>
						</li>
					@endforeach
				</ul>
			@endif

			@if($paymentReport->status==='reported')
				<hr>
				<div class="d-flex gap-2">
					<form method="POST" action="{{ route('payments.approve', $paymentReport) }}">
						@csrf
						@method('PATCH')
						<button class="btn btn-success" onclick="return confirm('¿Aprobar este pago?')">Aprobar</button>
					</form>

					<form method="POST" action="{{ route('payments.reject', $paymentReport) }}">
						@csrf
						@method('PATCH')
						<button class="btn btn-danger" onclick="return confirm('¿Rechazar este pago?')">Rechazar</button>
					</form>
				</div>
			@endif
		</div>
	</div>
@endsection
