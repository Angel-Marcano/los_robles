@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-pencil-square me-2"></i>Editar Condominio</h1>
	<a class="btn btn-outline-secondary btn-action" href="{{route('condominiums.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
	<div class="card-body">
		<form method="POST" action="{{route('condominiums.update',$condominium)}}">
			@csrf @method('PUT')
			<div class="mb-3">
				<label class="form-label">Nombre</label>
				<input name="name" class="form-control" value="{{$condominium->name}}" required>
			</div>
			<div class="form-check mb-3">
				<input type="checkbox" name="active" value="1" class="form-check-input" id="activeCheck" {{$condominium->active?'checked':''}}>
				<label class="form-check-label" for="activeCheck">Activo</label>
			</div>
			<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Actualizar</button>
		</form>
	</div>
</div>
@endsection
