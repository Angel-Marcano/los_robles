@extends('layouts.app')
@section('content')
<div class="row justify-content-center" style="min-height: 70vh; align-items: center;">
	<div class="col-md-5 col-lg-4">
		<div class="text-center mb-4">
			<i class="bi bi-buildings" style="font-size: 2.5rem; color: var(--bs-success);"></i>
			<h2 class="fw-bold mt-2">{{ $appName ?? 'Los Robles' }}</h2>
			<p class="text-muted">Ingresa a tu cuenta</p>
		</div>
		<div class="card">
			<div class="card-body p-4">
				<form method="POST" action="{{ route('login.perform') }}">
					@csrf
					<div class="mb-3">
						<label class="form-label">Email</label>
						<div class="input-group">
							<span class="input-group-text"><i class="bi bi-envelope"></i></span>
							<input name="email" type="email" class="form-control" value="{{ old('email') }}" required placeholder="tu@email.com" />
						</div>
					</div>
					<div class="mb-4">
						<label class="form-label">Contraseña</label>
						<div class="input-group">
							<span class="input-group-text"><i class="bi bi-lock"></i></span>
							<input name="password" type="password" class="form-control" required placeholder="••••••••" />
						</div>
					</div>
					<button class="btn btn-primary w-100 btn-action mb-3"><i class="bi bi-box-arrow-in-right me-1"></i> Entrar</button>
					<div class="text-center">
						<a href="{{ url('password/forgot') }}" class="text-muted small">¿Olvidaste tu contraseña?</a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection