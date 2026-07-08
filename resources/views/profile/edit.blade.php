@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-person-gear me-2"></i>Mi Perfil</h1>
</div>

@if(session('status'))
	<div class="alert alert-success alert-dismissible fade show">
		<i class="bi bi-check-circle me-1"></i>{{ session('status') }}
		<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
	</div>
@endif

@if($errors->any())
	<div class="alert alert-danger">
		<ul class="mb-0">
			@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
		</ul>
	</div>
@endif

<div class="row g-4">
	{{-- Datos personales --}}
	<div class="col-lg-6">
		<div class="card">
			<div class="card-header"><i class="bi bi-person me-1"></i>Datos personales</div>
			<div class="card-body">
				<form method="POST" action="{{ route('profile.update') }}">
					@csrf @method('PATCH')
					<div class="mb-3">
						<label class="form-label">Nombre</label>
						<input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required maxlength="120">
					</div>
					<div class="row g-3">
						<div class="col-md-6 mb-3">
							<label class="form-label">Nombre de pila</label>
							<input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" maxlength="80">
						</div>
						<div class="col-md-6 mb-3">
							<label class="form-label">Apellido</label>
							<input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" maxlength="120">
						</div>
					</div>
					<div class="row g-3">
						<div class="col-md-5 mb-3">
							<label class="form-label">Tipo de documento</label>
							<select name="document_type" class="form-select">
								<option value="">—</option>
								<option value="cedula" @if(old('document_type', $user->document_type) === 'cedula') selected @endif>Cédula</option>
								<option value="pasaporte" @if(old('document_type', $user->document_type) === 'pasaporte') selected @endif>Pasaporte</option>
							</select>
						</div>
						<div class="col-md-7 mb-3">
							<label class="form-label">Número de documento</label>
							<input type="text" name="document_number" class="form-control" value="{{ old('document_number', $user->document_number) }}" maxlength="40">
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label">Correo electrónico</label>
						<input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
					</div>
					<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar cambios</button>
				</form>
			</div>
		</div>
	</div>

	{{-- Cambiar contraseña --}}
	<div class="col-lg-6">
		<div class="card">
			<div class="card-header"><i class="bi bi-shield-lock me-1"></i>Cambiar contraseña</div>
			<div class="card-body">
				<form method="POST" action="{{ route('profile.password') }}">
					@csrf @method('PATCH')
					<div class="mb-3">
						<label class="form-label">Contraseña actual</label>
						<input type="password" name="current_password" class="form-control" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Nueva contraseña</label>
						<input type="password" name="password" class="form-control" required minlength="6">
					</div>
					<div class="mb-3">
						<label class="form-label">Confirmar nueva contraseña</label>
						<input type="password" name="password_confirmation" class="form-control" required minlength="6">
					</div>
					<button class="btn btn-warning btn-action"><i class="bi bi-key"></i> Cambiar contraseña</button>
				</form>
			</div>
		</div>
	</div>

	{{-- Verificación en dos pasos --}}
	<div class="col-lg-6">
		<div class="card">
			<div class="card-header"><i class="bi bi-shield-check me-1"></i>Verificación en dos pasos (2FA)</div>
			<div class="card-body">
				@if($user->twoFactorEnabled())
					<p>
						<span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Activada</span>
						<span class="text-muted ms-2">Método: <strong>{{ $user->two_factor_method === 'totp' ? 'App autenticadora' : 'Código por correo' }}</strong></span>
					</p>
					<p class="text-muted small">Al iniciar sesión se te pedirá un código adicional.</p>
					<form method="POST" action="{{ route('profile.2fa.disable') }}" class="row g-2 align-items-end">
						@csrf @method('DELETE')
						<div class="col-md-7">
							<label class="form-label">Contraseña actual</label>
							<input type="password" name="password" class="form-control" required>
						</div>
						<div class="col-md-5">
							<button class="btn btn-outline-danger btn-action w-100"><i class="bi bi-shield-x"></i> Desactivar</button>
						</div>
					</form>
				@elseif($user->two_factor_method)
					{{-- Activación pendiente de confirmar --}}
					<p><span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pendiente de confirmación</span>
						<span class="text-muted ms-2">Método: <strong>{{ $user->two_factor_method === 'totp' ? 'App autenticadora' : 'Código por correo' }}</strong></span>
					</p>
					@if($user->two_factor_method === 'totp')
						@php
							$qrSvg = \App\Http\Controllers\TwoFactorSettingsController::totpQrSvg($user);
							$totpSecret = \App\Http\Controllers\TwoFactorSettingsController::totpSecret($user);
						@endphp
						<div class="text-center mb-3">
							@if($qrSvg){!! $qrSvg !!}@endif
							<div class="text-muted small mt-2">¿No puedes escanear? Ingresa esta clave manualmente:</div>
							<code class="user-select-all">{{ $totpSecret }}</code>
						</div>
						<p class="text-muted small">Escanea el QR con Google Authenticator, Authy o similar, y confirma con el código de 6 dígitos.</p>
					@else
						<p class="text-muted small">Revisa tu correo <strong>{{ $user->email }}</strong> e ingresa el código de 6 dígitos (expira en 10 minutos).</p>
					@endif
					<form method="POST" action="{{ route('profile.2fa.confirm') }}" class="row g-2 align-items-end mb-2">
						@csrf
						<div class="col-md-7">
							<label class="form-label">Código de verificación</label>
							<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" class="form-control font-monospace" placeholder="123456" required>
						</div>
						<div class="col-md-5">
							<button class="btn btn-success btn-action w-100"><i class="bi bi-check-lg"></i> Confirmar</button>
						</div>
					</form>
					<div class="d-flex gap-2">
						@if($user->two_factor_method === 'email')
						<form method="POST" action="{{ route('profile.2fa.email') }}">
							@csrf
							<button class="btn btn-link btn-sm text-muted p-0">Reenviar código</button>
						</form>
						@endif
						<form method="POST" action="{{ route('profile.2fa.cancel') }}" class="ms-auto">
							@csrf
							<button class="btn btn-link btn-sm text-danger p-0">Cancelar activación</button>
						</form>
					</div>
				@else
					<p><span class="badge bg-secondary">Desactivada</span></p>
					<p class="text-muted small">Agrega una capa extra de seguridad: al iniciar sesión se pedirá un código además de tu contraseña.</p>
					<div class="d-flex gap-2 flex-wrap">
						<form method="POST" action="{{ route('profile.2fa.email') }}">
							@csrf
							<button class="btn btn-outline-primary btn-action"><i class="bi bi-envelope"></i> Activar por correo</button>
						</form>
						<form method="POST" action="{{ route('profile.2fa.totp') }}">
							@csrf
							<button class="btn btn-outline-primary btn-action"><i class="bi bi-phone"></i> Activar con app autenticadora</button>
						</form>
					</div>
				@endif
			</div>
		</div>
	</div>

	{{-- Info de cuenta --}}
	<div class="col-lg-6">
		<div class="card">
			<div class="card-header"><i class="bi bi-info-circle me-1"></i>Información de cuenta</div>
			<div class="card-body">
				<dl class="row mb-0">
					<dt class="col-sm-4 text-muted">ID</dt>
					<dd class="col-sm-8">{{ $user->id }}</dd>
					<dt class="col-sm-4 text-muted">Roles</dt>
					<dd class="col-sm-8">
						@forelse($user->roles as $role)
							<span class="badge bg-primary me-1">{{ $role->name }}</span>
						@empty
							<span class="text-muted">Sin roles</span>
						@endforelse
					</dd>
					<dt class="col-sm-4 text-muted">Estado</dt>
					<dd class="col-sm-8">
						@if($user->active)
							<span class="badge bg-success">Activo</span>
						@else
							<span class="badge bg-secondary">Inactivo</span>
						@endif
					</dd>
					<dt class="col-sm-4 text-muted">Registrado</dt>
					<dd class="col-sm-8">{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
				</dl>
			</div>
		</div>
	</div>
</div>
@endsection
