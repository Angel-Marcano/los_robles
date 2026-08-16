@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-currency-exchange me-2"></i>Nueva Tasa</h1>
	<a class="btn btn-outline-secondary btn-action" href="{{route('rates.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
	<div class="card-body">
		<form method="POST" action="{{route('rates.store')}}">
			@csrf
			<div class="mb-3">
				<label class="form-label">Tasa USD&rarr;VES</label>
				<input name="rate" type="number" step="0.000001" class="form-control" required>
			</div>
			<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar</button>
		</form>
	</div>
</div>
@endsection
