@extends('layouts.app')

@section('content')
<div class="row justify-content-center" style="min-height: 60vh; align-items: center;">
	<div class="col-md-5 col-lg-4">
		<div class="text-center mb-4">
			<i class="bi bi-envelope-open" style="font-size: 2.5rem; color: var(--bs-primary);"></i>
			<h2 class="fw-bold mt-2">Recuperar contraseña</h2>
			<p class="text-muted">Te enviaremos un enlace seguro a tu correo.</p>
		</div>
		<div class="card">
			<div class="card-body p-4">
				@if(session('status'))
					<div class="alert alert-success d-flex align-items-center gap-2" role="alert">
						<i class="bi bi-check-circle-fill"></i>
						<div>{{ session('status') }}</div>
					</div>
				@endif
				@if($errors->any())
					<div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
						<i class="bi bi-exclamation-circle-fill"></i>
						<div>{{ $errors->first() }}</div>
					</div>
				@endif
				<form method="POST" action="{{ route('password.forgot.send') }}" novalidate>
					@csrf
					<div class="mb-3">
						<label for="email" class="form-label">Correo electrónico</label>
						<div class="input-group">
							<span class="input-group-text"><i class="bi bi-envelope"></i></span>
							<input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email" placeholder="tu@email.com">
						</div>
						@error('email')
							<div class="invalid-feedback d-block">{{ $message }}</div>
						@enderror
					</div>
					<button class="btn btn-primary w-100 btn-action mb-3" type="submit">
						<i class="bi bi-send me-1"></i> Enviar enlace de recuperación
					</button>
					<div class="text-center">
						<a href="{{ route('login') }}" class="text-muted small">
							<i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection
