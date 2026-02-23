@extends('layouts.app')
@section('content')
<div class="card">
	<div class="card-body">
		<h3 class="card-title mb-4">Editar Usuario</h3>

		@if($errors->any())
			<div class="alert alert-danger">{{ $errors->first() }}</div>
		@endif

		<form method="POST" action="{{ route('users.update', $user) }}">
			@csrf
			@method('PUT')

			<div class="row">
				<div class="col-md-6 mb-3">
					<label class="form-label">Nombre Usuario (alias)</label>
					<input name="name" class="form-control" value="{{ old('name', $user->name) }}" required />
				</div>
				<div class="col-md-6 mb-3">
					<label class="form-label">Email</label>
					<input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required />
				</div>
			</div>

			<div class="row">
				<div class="col-md-6 mb-3">
					<label class="form-label">Nombres</label>
					<input name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required />
				</div>
				<div class="col-md-6 mb-3">
					<label class="form-label">Apellidos</label>
					<input name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required />
				</div>
			</div>

			<div class="row">
				<div class="col-md-4 mb-3">
					<label class="form-label">Tipo Documento</label>
					<select name="document_type" class="form-select" required>
						<option value="cedula" @selected(old('document_type', $user->document_type)=='cedula')>Cédula</option>
						<option value="pasaporte" @selected(old('document_type', $user->document_type)=='pasaporte')>Pasaporte</option>
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label">Número Documento</label>
					<input name="document_number" class="form-control" value="{{ old('document_number', $user->document_number) }}" required />
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label">Password (opcional)</label>
					<input name="password" type="password" class="form-control" />
				</div>
			</div>

			<div class="mb-3 form-check">
				<input type="checkbox" class="form-check-input" id="activeCheck" name="active" value="1" @checked(old('active', $user->active))>
				<label class="form-check-label" for="activeCheck">Activo</label>
			</div>

			@if(isset($roles) && count($roles))
			<div class="mb-3">
				<label class="form-label">Roles</label>
				<div>
					@foreach($roles as $role)
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="checkbox" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->name }}" @checked(in_array($role->name, old('roles', $user->getRoleNames()->toArray() ?? [])))>
							<label class="form-check-label" for="role_{{ $role->id }}">{{ $role->name }}</label>
						</div>
					@endforeach
				</div>
			</div>
			@endif

			<div class="d-flex gap-2">
				<button class="btn btn-primary">Actualizar</button>
				<a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
			</div>
		</form>
	</div>
</div>

@endsection
