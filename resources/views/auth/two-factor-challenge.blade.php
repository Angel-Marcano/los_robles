@extends('layouts.app')
@section('content')
<div class="row justify-content-center" style="min-height: 70vh; align-items: center;">
	<div class="col-md-5 col-lg-4">
		<div class="text-center mb-4">
			<i class="bi bi-shield-lock" style="font-size: 2.5rem; color: var(--bs-success);"></i>
			<h2 class="fw-bold mt-2">Verificación en dos pasos</h2>
			@if($method === 'email')
				<p class="text-muted">Te enviamos un código de 6 dígitos a tu correo.</p>
			@else
				<p class="text-muted">Ingresa el código de tu app autenticadora.</p>
			@endif
		</div>
		<div class="card">
			<div class="card-body p-4">
				@if(session('status'))
					<div class="alert alert-success py-2">{{ session('status') }}</div>
				@endif
				@if($errors->any())
					<div class="alert alert-danger py-2">{{ $errors->first() }}</div>
				@endif
				<form method="POST" action="{{ route('2fa.verify') }}">
					@csrf
					<div class="mb-4">
						<label class="form-label">Código</label>
						<input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="10"
							class="form-control form-control-lg text-center font-monospace" style="letter-spacing: 8px;"
							placeholder="••••••" required autofocus />
					</div>
					<button class="btn btn-primary w-100 btn-action mb-3"><i class="bi bi-check-lg me-1"></i> Verificar</button>
				</form>
				@if($method === 'email')
				<form method="POST" action="{{ route('2fa.resend') }}" class="text-center">
					@csrf
					<button type="submit" class="btn btn-link text-muted small p-0">¿No recibiste el código? Reenviar</button>
				</form>
				@endif
				<div class="text-center mt-2">
					<a href="{{ route('login') }}" class="text-muted small">Volver al inicio de sesión</a>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
