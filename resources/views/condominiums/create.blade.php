@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-building-add me-2"></i>Crear Condominio</h1>
	<a class="btn btn-outline-secondary btn-action" href="{{route('condominiums.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
	<div class="card-body">
		<form method="POST" action="{{route('condominiums.store')}}">
			@csrf
			<div class="mb-3">
				<label class="form-label">Nombre</label>
				<input name="name" class="form-control" required>
			</div>
			<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar</button>
		</form>
	</div>
</div>
@endsection
