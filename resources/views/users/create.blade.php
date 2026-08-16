@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-person-plus me-2"></i>Crear Usuario</h1>
	<a class="btn btn-outline-secondary btn-action" href="{{ route('users.index') }}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
	<div class="card-body">
		@if($errors->any())
			<div class="alert alert-danger">{{ $errors->first() }}</div>
		@endif

		<form method="POST" action="{{ route('users.store') }}">
			@csrf
			<div class="row">
				<div class="col-md-6 mb-3">
					<label class="form-label">Nombre Usuario (alias)</label>
					<input name="name" class="form-control" value="{{ old('name') }}" required />
				</div>
				<div class="col-md-6 mb-3">
					<label class="form-label">Email</label>
					<input name="email" type="email" class="form-control" value="{{ old('email') }}" required />
				</div>
			</div>

			<div class="row">
				<div class="col-md-6 mb-3">
					<label class="form-label">Nombres</label>
					<input name="first_name" class="form-control" value="{{ old('first_name') }}" required />
				</div>
				<div class="col-md-6 mb-3">
					<label class="form-label">Apellidos</label>
					<input name="last_name" class="form-control" value="{{ old('last_name') }}" required />
				</div>
			</div>

			<div class="row">
				<div class="col-md-4 mb-3">
					<label class="form-label">Tipo Documento</label>
					<select name="document_type" class="form-select" required>
						<option value="cedula" @selected(old('document_type')=='cedula')>Cédula</option>
						<option value="pasaporte" @selected(old('document_type')=='pasaporte')>Pasaporte</option>
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label">Número Documento</label>
					<input name="document_number" class="form-control" value="{{ old('document_number') }}" required />
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label">Teléfono</label>
					<input name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+58 412 ..." />
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label">Password</label>
				<input name="password" type="password" class="form-control" required />
			</div>

			@if(isset($roles) && count($roles))
			<div class="mb-3">
				<label class="form-label">Asignar Roles</label>
				<div>
					@foreach($roles as $role)
						@php
							$labels = [
								'super_admin'  => 'Super Admin (acceso total)',
								'condo_admin'  => 'Admin de Condominio',
								'tower_admin'  => 'Admin de Torre',
								'owner'        => 'Propietario',
								'co_owner'     => 'Copropietario',
								'tenant'       => 'Inquilino / Arrendatario',
							];
							$label = $labels[$role->name] ?? $role->name;
						@endphp
						<div class="form-check">
							<input class="form-check-input role-checkbox" type="checkbox" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->name }}" data-role="{{ $role->name }}">
							<label class="form-check-label" for="role_{{ $role->id }}">{{ $label }}</label>
						</div>
					@endforeach
				</div>
			</div>
			@endif

			@php
				$apartmentRoleNames = ['owner' => 'Propietario', 'co_owner' => 'Copropietario', 'tenant' => 'Inquilino / Arrendatario'];
			@endphp

			{{-- Torres administradas (solo si marca tower_admin) --}}
			<div id="towerAdminSection" style="display:none">
				@if(isset($towers) && count($towers))
				<div class="mb-3">
					<label class="form-label"><i class="bi bi-building text-primary"></i> Torres administradas</label>
					<p class="text-muted small mb-2">Selecciona las torres que administrará este usuario.</p>
					<div>
						@foreach($towers as $tower)
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="tower_ids[]" id="tower_{{ $tower->id }}" value="{{ $tower->id }}">
								<label class="form-check-label" for="tower_{{ $tower->id }}">{{ $tower->name }}</label>
							</div>
						@endforeach
					</div>
				</div>
				@endif
			</div>

			{{-- Info para condo_admin --}}
			<div id="condoAdminSection" style="display:none">
				<div class="alert alert-info py-2">
					<i class="bi bi-info-circle me-1"></i>
					<strong>Admin de Condominio</strong> administra automáticamente todo el condominio actual (determinado por el subdominio). No necesita asignar torres o apartamentos específicos.
				</div>
			</div>

			{{-- Apartamentos (solo si marca owner, co_owner o tenant) --}}
			<div id="apartmentSection" style="display:none">
				@if(isset($towers) && count($towers))
				<div class="mb-3">
					<label class="form-label"><i class="bi bi-door-open text-success"></i> Apartamentos</label>
					<p class="text-muted small mb-2">Selecciona los apartamentos que pertenecen a este usuario. Puede tener varios, de la misma o diferente torre.</p>

					<div class="mb-2">
						<label class="form-label small fw-bold">Tipo de relación:</label>
						<select name="ownership_role" class="form-select form-select-sm" style="max-width:280px">
							<option value="owner">Propietario</option>
							<option value="co_owner">Copropietario</option>
							<option value="tenant">Inquilino / Arrendatario</option>
						</select>
					</div>

					<div class="mb-2 d-flex gap-2 align-items-center">
						<input type="text" id="aptSearch" class="form-control form-control-sm" style="max-width:260px" placeholder="Buscar apartamento..." oninput="filterApartments()">
						<button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAptFilter()">Limpiar</button>
					</div>

					<div class="border rounded p-2" style="max-height:280px;overflow-y:auto">
						@foreach($towers as $tower)
							<div class="tower-group mb-2" data-tower="{{ strtolower($tower->name) }}">
								<div class="fw-semibold small text-secondary mb-1">{{ $tower->name }}</div>
								@foreach($tower->apartments as $apt)
									<div class="form-check ms-3 apt-item" data-apt="{{ strtolower($apt->code) }}">
										<input class="form-check-input apt-checkbox" type="checkbox" name="apartment_ids[]" id="apt_{{ $apt->id }}" value="{{ $apt->id }}">
										<label class="form-check-label" for="apt_{{ $apt->id }}">{{ $apt->code }} <span class="text-muted small">({{ number_format($apt->aliquot_percent, 2) }}%)</span></label>
									</div>
								@endforeach
							</div>
						@endforeach
					</div>
				</div>
				@endif
			</div>

			<div class="d-flex gap-2">
				<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar</button>
				<a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-action"><i class="bi bi-x-lg"></i> Cancelar</a>
			</div>
		</form>
	</div>
</div>

<script>
// Mostrar/ocultar secciones según roles marcados
function updateSections() {
	const checked = Array.from(document.querySelectorAll('.role-checkbox:checked')).map(cb => cb.dataset.role);
	const isTowerAdmin = checked.includes('tower_admin');
	const isCondoAdmin = checked.includes('condo_admin');
	const hasAptRole = checked.includes('owner') || checked.includes('co_owner') || checked.includes('tenant');

	document.getElementById('towerAdminSection').style.display = isTowerAdmin ? '' : 'none';
	document.getElementById('condoAdminSection').style.display = isCondoAdmin ? '' : 'none';
	document.getElementById('apartmentSection').style.display = hasAptRole ? '' : 'none';

	if (!isTowerAdmin) {
		document.querySelectorAll('input[name="tower_ids[]"]').forEach(cb => cb.checked = false);
	}
	if (!hasAptRole) {
		document.querySelectorAll('input[name="apartment_ids[]"]').forEach(cb => cb.checked = false);
	}

	// Auto-seleccionar el tipo de relación según el rol marcado
	const roleSelect = document.querySelector('select[name="ownership_role"]');
	if (roleSelect && hasAptRole) {
		if (checked.includes('tenant')) roleSelect.value = 'tenant';
		else if (checked.includes('co_owner')) roleSelect.value = 'co_owner';
		else if (checked.includes('owner')) roleSelect.value = 'owner';
	}
}
document.querySelectorAll('.role-checkbox').forEach(cb => cb.addEventListener('change', updateSections));

// Filtro de apartamentos
function filterApartments() {
	const term = document.getElementById('aptSearch').value.toLowerCase().trim();
	document.querySelectorAll('.apt-item').forEach(item => {
		const match = item.dataset.apt.includes(term);
		item.style.display = match ? '' : 'none';
	});
	document.querySelectorAll('.tower-group').forEach(group => {
		const visible = Array.from(group.querySelectorAll('.apt-item')).some(i => i.style.display !== 'none');
		group.style.display = visible ? '' : 'none';
	});
}
function clearAptFilter() {
	document.getElementById('aptSearch').value = '';
	filterApartments();
}
</script>

@endsection
