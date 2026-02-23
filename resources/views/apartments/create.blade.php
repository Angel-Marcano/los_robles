@extends('layouts.app')
@section('content')
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h4 m-0">Nuevo Apartamento en Torre {{ $tower->name }}</h1>
	</div>
	<form method="POST" action="{{ route('towers.apartments.store',$tower) }}">
		@csrf
		<div class="mb-3">
			<label class="form-label">Código</label>
			<input name="code" class="form-control" value="{{ old('code') }}" required>
			@error('code')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<div class="mb-3">
			<label class="form-label">Alícuota (%)</label>
			<input name="aliquot_percent" step="0.0001" type="number" class="form-control" value="{{ old('aliquot_percent') }}" required placeholder="2.34">
			<div class="form-text">Valor porcentual usado para prorratear gastos.</div>
			@error('aliquot_percent')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<div class="form-check mb-3">
			<input type="hidden" name="active" value="0">
			<input class="form-check-input" type="checkbox" name="active" value="1" id="activeCheck" {{ old('active', true) ? 'checked' : '' }}>
			<label class="form-check-label" for="activeCheck">Activo</label>
			@error('active')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<hr>
		<h2 class="h6">Propietario (opcional)</h2>
		<div class="form-text mb-2">Si completas estos campos, se creará el usuario y se asociará como propietario del apartamento.</div>
		<div class="mb-3">
			<label class="form-label">Nombre</label>
			<input name="owner_name" class="form-control" value="{{ old('owner_name') }}">
			@error('owner_name')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<div class="mb-3">
			<label class="form-label">Email</label>
			<input type="email" name="owner_email" class="form-control" value="{{ old('owner_email') }}">
			@error('owner_email')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<div class="mb-3">
			<label class="form-label">Contraseña</label>
			<input type="text" name="owner_password" class="form-control" value="{{ old('owner_password') }}" placeholder="Por defecto 1234 si se deja vacío">
			@error('owner_password')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<button class="btn btn-primary">Guardar</button>
		<a href="{{ route('towers.apartments.index',$tower) }}" class="btn btn-secondary ms-2">Volver</a>
	</form>
</div>
@endsection
