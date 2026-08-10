@extends('layouts.app')

@section('content')
<div class="row justify-content-center" style="min-height: 60vh; align-items: center;">
	<div class="col-md-5 col-lg-4">
		<div class="text-center mb-4">
			<i class="bi bi-shield-lock" style="font-size: 2.5rem; color: var(--bs-primary);"></i>
			<h2 class="fw-bold mt-2">Restablecer contraseña</h2>
			<p class="text-muted">Crea una contraseña segura para tu cuenta.</p>
		</div>
		<div class="card">
			<div class="card-body p-4">
				@if($errors->any())
					<div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
						<i class="bi bi-exclamation-circle-fill"></i>
						<div>{{ $errors->first() }}</div>
					</div>
				@endif
				<form method="POST" action="{{ route('password.reset.update') }}" novalidate>
					@csrf
					<input type="hidden" name="token" value="{{ $token }}">
					<input type="hidden" name="email" value="{{ $email }}">

					<div class="mb-3">
						<label for="password" class="form-label">Nueva contraseña</label>
						<div class="input-group">
							<span class="input-group-text"><i class="bi bi-lock"></i></span>
							<input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password" placeholder="••••••••">
							<button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Mostrar contraseña" title="Mostrar contraseña">
								<i class="bi bi-eye"></i>
							</button>
						</div>
						@error('password')
							<div class="invalid-feedback d-block">{{ $message }}</div>
						@enderror
						<div class="form-text">Mínimo 8 caracteres, una mayúscula, una minúscula y un número.</div>
					</div>

					<div class="mb-4">
						<label for="password_confirmation" class="form-label">Confirmar contraseña</label>
						<div class="input-group">
							<span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
							<input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password" placeholder="••••••••">
						</div>
					</div>

					<button class="btn btn-primary w-100 btn-action" type="submit">
						<i class="bi bi-check-lg me-1"></i> Actualizar contraseña
					</button>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
	const passwordInput = document.getElementById('password');
	const toggleButton = document.getElementById('togglePassword');
	if(!passwordInput || !toggleButton) return;

	toggleButton.addEventListener('click', function(){
		const isPassword = passwordInput.type === 'password';
		passwordInput.type = isPassword ? 'text' : 'password';
		toggleButton.innerHTML = isPassword
			? '<i class="bi bi-eye-slash"></i>'
			: '<i class="bi bi-eye"></i>';
		toggleButton.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
		toggleButton.setAttribute('title', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
	});
});
</script>
@endpush
