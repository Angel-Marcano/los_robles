@extends('layouts.app')
@section('content')
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h4 m-0">Editar Apartamento</h1>
	</div>
	@if(session('status'))
		<div class="alert alert-success">{{ session('status') }}</div>
	@endif
		<form method="POST" action="{{ route('apartments.update',$apartment) }}">
		@csrf @method('PUT')
		<div class="mb-3">
			<label class="form-label">Código</label>
			<input name="code" class="form-control" value="{{ old('code',$apartment->code) }}" required>
			@error('code')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<div class="mb-3">
			<label class="form-label">Alícuota (%)</label>
			<input name="aliquot_percent" step="0.0001" type="number" class="form-control" value="{{ old('aliquot_percent',$apartment->aliquot_percent) }}" required>
			<div class="form-text">Valor porcentual usado para prorratear gastos.</div>
			@error('aliquot_percent')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<div class="form-check mb-3">
			<input type="hidden" name="active" value="0">
			<input class="form-check-input" type="checkbox" name="active" value="1" id="activeCheck" {{ old('active',$apartment->active) ? 'checked' : '' }}>
			<label class="form-check-label" for="activeCheck">Activo</label>
			@error('active')<div class="text-danger small">{{ $message }}</div>@enderror
		</div>
		<hr>
		<h2 class="h6">Propietario (opcional)</h2>
		<div class="form-text mb-2">Para asociar un propietario, ingresa su email; si no existe se creará con la contraseña indicada.</div>
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
		<button class="btn btn-primary">Actualizar</button>
		<a href="{{ route('towers.apartments.index',$tower) }}" class="btn btn-secondary ms-2">Volver</a>
	</form>

		<hr class="my-4">
		<div class="d-flex justify-content-between align-items-center mb-2">
			<h2 class="h6 m-0">Propietarios actuales</h2>
			<a class="btn btn-sm btn-outline-secondary" href="{{ route('ownerships.index',$apartment) }}">Gestionar Propietarios</a>
		</div>
		@php($owners = $apartment->ownerships()->with('user')->get())
		@if($owners->count() === 0)
			<div class="alert alert-light">Este apartamento no tiene propietarios asociados.</div>
		@else
			<div class="table-responsive">
				<table class="table table-sm table-bordered">
					<thead class="table-light">
						<tr>
							<th>Usuario</th>
							<th>Email</th>
								<th>Documento</th>
							<th>Activo</th>
								<th>Acciones</th>
						</tr>
					</thead>
					<tbody>
						@foreach($owners as $own)
							<tr>
								<td>{{ optional($own->user)->name }}</td>
								<td>{{ optional($own->user)->email }}</td>
																	<td>
																		@if($own->user)
																			<form method="POST" action="{{ route('users.update', $own->user) }}" class="d-flex align-items-center gap-2" onsubmit="return validateDoc(this)">
																				@csrf @method('PUT')
																				<select name="document_type" class="form-select form-select-sm" style="width:auto">
																					<option value="cedula" {{ $own->user->document_type === 'cedula' ? 'selected' : '' }}>Cédula</option>
																					<option value="pasaporte" {{ $own->user->document_type === 'pasaporte' ? 'selected' : '' }}>Pasaporte</option>
																				</select>
																				<input type="text" name="document_number" value="{{ $own->user->document_number }}" class="form-control form-control-sm" placeholder="Número" minlength="5">
																				<button class="btn btn-sm btn-outline-primary">Guardar</button>
																			</form>
																		@else
																			<span class="text-muted">Sin usuario</span>
																		@endif
																	</td>
								<td>
									<span class="badge {{ ($own->active ?? true) ? 'bg-success' : 'bg-secondary' }}">{{ ($own->active ?? true) ? 'Sí' : 'No' }}</span>
								</td>
									<td>
														<form method="POST" action="{{ route('ownerships.toggle', [$apartment, $own]) }}" style="display:inline" onsubmit="return confirm('¿Seguro que quieres cambiar el estado del propietario?')">
											@csrf @method('PATCH')
											<button class="btn btn-sm {{ ($own->active ?? true) ? 'btn-outline-warning' : 'btn-outline-success' }}">
												{{ ($own->active ?? true) ? 'Desactivar' : 'Activar' }}
											</button>
										</form>
									</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		@endif
</div>
@endsection

@push('scripts')
<script>
function validateDoc(form){
	const type = form.querySelector('[name="document_type"]').value;
	const number = form.querySelector('[name="document_number"]').value.trim();
	if(type && !number){
		alert('Debes ingresar el número de documento.');
		return false;
	}
	return true;
}
</script>
@endpush
