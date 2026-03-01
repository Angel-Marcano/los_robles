@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-door-open me-2"></i>Nuevo Apartamento — Torre {{ $tower->name }}</h1>
	<a class="btn btn-outline-secondary btn-action" href="{{ route('towers.apartments.index',$tower) }}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
	<div class="card-body">
		<form method="POST" action="{{ route('towers.apartments.store',$tower) }}">
			@csrf
			@if($errors->any())
				<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>
			@endif
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label">Código</label>
					<input name="code" class="form-control" value="{{ old('code') }}" required>
					@error('code')<div class="text-danger small">{{ $message }}</div>@enderror
				</div>
				<div class="col-md-6">
					<label class="form-label">Alícuota (%)</label>
					<input name="aliquot_percent" step="0.0001" type="number" class="form-control" value="{{ old('aliquot_percent') }}" required placeholder="2.34">
					<div class="form-text">Valor porcentual usado para prorratear gastos.</div>
					@error('aliquot_percent')<div class="text-danger small">{{ $message }}</div>@enderror
				</div>
			</div>
			<div class="form-check my-3">
				<input type="hidden" name="active" value="0">
				<input class="form-check-input" type="checkbox" name="active" value="1" id="activeCheck" {{ old('active', true) ? 'checked' : '' }}>
				<label class="form-check-label" for="activeCheck">Activo</label>
				@error('active')<div class="text-danger small">{{ $message }}</div>@enderror
			</div>
			<hr>
			<h2 class="h6"><i class="bi bi-person me-1"></i>Propietario (opcional)</h2>
			<div class="form-text mb-2">Si completas estos campos, se creará el usuario y se asociará como propietario del apartamento.</div>
			<div class="row g-3">
				<div class="col-md-4">
					<label class="form-label">Nombre</label>
					<input name="owner_name" class="form-control" value="{{ old('owner_name') }}">
					@error('owner_name')<div class="text-danger small">{{ $message }}</div>@enderror
				</div>
				<div class="col-md-4">
					<label class="form-label">Email</label>
					<input type="email" name="owner_email" class="form-control" value="{{ old('owner_email') }}">
					@error('owner_email')<div class="text-danger small">{{ $message }}</div>@enderror
				</div>
				<div class="col-md-4">
					<label class="form-label">Contraseña</label>
					<input type="text" name="owner_password" class="form-control" value="{{ old('owner_password') }}" placeholder="Por defecto 1234 si se deja vacío">
					@error('owner_password')<div class="text-danger small">{{ $message }}</div>@enderror
				</div>
			</div>
			<div class="mt-3 d-flex gap-2">
				<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar</button>
				<a href="{{ route('towers.apartments.index',$tower) }}" class="btn btn-outline-secondary btn-action"><i class="bi bi-x-lg"></i> Cancelar</a>
			</div>
		</form>
	</div>
</div>
@endsection
