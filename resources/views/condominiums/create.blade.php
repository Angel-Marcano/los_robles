@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-building-add me-2"></i>Crear Condominio</h1>
	<a class="btn btn-outline-secondary btn-action" href="{{route('condominiums.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
	<div class="card-body">
		@if(session('status'))
			<div class="alert alert-success">{{ session('status') }}</div>
		@endif
		@if($errors->any())
			<div class="alert alert-danger">{{ $errors->first() }}</div>
		@endif

		<form method="POST" action="{{route('condominiums.store')}}">
			@csrf
			<div class="mb-3">
				<label class="form-label">Nombre del Condominio</label>
				<input name="name" class="form-control" value="{{ old('name') }}" required />
			</div>

			<div class="mb-3">
				<label class="form-label">Dominio o Subdominio</label>
				<input name="subdomain" id="subdomainInput" class="form-control" value="{{ old('subdomain') }}" required placeholder="ej: laspalmas.com o demo" oninput="updatePreview()" />
				<div class="form-text">
					URL de acceso: <strong id="urlPreview">https://__</strong>
					<br><small class="text-muted">Puede ser un dominio completo (laspalmas.com) o un subdominio (demo)</small>
				</div>
			</div>

			<hr class="my-3">
			<h6 class="text-muted mb-3"><i class="bi bi-person-gear me-1"></i> Usuario Administrador</h6>

			<div class="mb-3">
				<label class="form-label">Email del Admin</label>
				<input name="admin_email" type="email" class="form-control" value="{{ old('admin_email') }}" required />
			</div>

			<div class="mb-3">
				<label class="form-label">Contraseña del Admin</label>
				<input name="admin_password" type="password" class="form-control" required minlength="6" />
			</div>

			<div class="alert alert-info py-2">
				<i class="bi bi-info-circle me-1"></i>
				Al crear el condominio se generará automáticamente su base de datos, migraciones, roles y usuario administrador.
			</div>

			<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Crear Condominio</button>
		</form>
	</div>
</div>

<script>
function updatePreview() {
	const val = document.getElementById('subdomainInput').value.trim();
	const preview = document.getElementById('urlPreview');
	if (val.includes('.')) {
		// Dominio completo
		preview.textContent = 'https://' + val;
	} else if (val) {
		// Subdominio local
		preview.textContent = 'http://' + val + '.test';
	} else {
		preview.textContent = 'https://__';
	}
}
</script>
@endsection
