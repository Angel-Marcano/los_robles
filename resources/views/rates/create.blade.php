@extends('layouts.app')
@section('content')
<div class="container">
	<h1 class="mb-4">Nueva Tasa</h1>
	<form method="POST" action="{{route('rates.store')}}" class="card p-4 shadow-sm">
		@csrf
		<div class="mb-3">
			<label class="form-label">Tasa USD→VES</label>
			<input name="rate" type="number" step="0.000001" class="form-control" required>
		</div>
		<button class="btn btn-primary">Guardar</button>
	</form>
</div>
@endsection
