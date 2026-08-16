@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-plus-circle me-2"></i>Nueva Votación</h1>
	<a href="{{ route('assemblies.index') }}" class="btn btn-outline-secondary btn-action"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card">
	<div class="card-body">
		<form method="POST" action="{{ route('assemblies.store') }}">
			@csrf
			<div class="mb-3">
				<label class="form-label">Título</label>
				<input type="text" name="title" class="form-control" value="{{ old('title') }}" required maxlength="200">
			</div>

			<div class="mb-3">
				<label class="form-label">Descripción</label>
				<textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
			</div>

			<div class="row g-3 mb-3">
				<div class="col-md-4">
					<label class="form-label">Alcance</label>
					<select name="scope" class="form-select" id="scopeSelect" onchange="toggleTowers()">
						<option value="condo" @selected(old('scope') === 'condo')>Condominio completo</option>
						<option value="tower" @selected(old('scope') === 'tower')>Torre(s) específica(s)</option>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label">Tipo de voto</label>
					<select name="vote_type" class="form-select">
						<option value="public" @selected(old('vote_type') === 'public')>Público (se ve quién votó)</option>
						<option value="secret" @selected(old('vote_type') === 'secret')>Oculto (anónimo)</option>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label">Peso del voto</label>
					<select name="weight_mode" class="form-select">
						<option value="equal" @selected(old('weight_mode') === 'equal')>1 voto por apartamento</option>
						<option value="aliquot" @selected(old('weight_mode') === 'aliquot')>Por alícuota (%)</option>
					</select>
				</div>
			</div>

			<div class="row g-3 mb-3">
				<div class="col-md-4">
					<label class="form-label">Quórum</label>
					<select name="quorum_type" class="form-select" id="quorumSelect" onchange="toggleQuorum()">
						<option value="none" @selected(old('quorum_type') === 'none')>Sin límite (informativa)</option>
						<option value="simple" @selected(old('quorum_type') === 'simple')>Mayoría simple (50%+1)</option>
						<option value="qualified" @selected(old('quorum_type') === 'qualified')>Mayoría calificada (2/3)</option>
					</select>
				</div>
				<div class="col-md-4" id="quorumValueDiv" style="display:none">
					<label class="form-label">Porcentaje mínimo</label>
					<input type="number" name="quorum_value" class="form-control" value="{{ old('quorum_value', 50) }}" min="1" max="100" step="0.01">
				</div>
				<div class="col-md-4">
					<label class="form-label">Fecha de cierre (opcional)</label>
					<input type="datetime-local" name="closes_at" class="form-control" value="{{ old('closes_at') }}">
				</div>
			</div>

			<div id="towerSelectDiv" style="display:none" class="mb-3">
				<label class="form-label">Torres involucradas</label>
				<div class="d-flex flex-wrap gap-3">
					@foreach($towers as $tower)
					<div class="form-check">
						<input class="form-check-input" type="checkbox" name="tower_ids[]" value="{{ $tower->id }}" id="tw_{{ $tower->id }}" @checked(in_array($tower->id, old('tower_ids', [])))>
						<label class="form-check-label" for="tw_{{ $tower->id }}">{{ $tower->name }}</label>
					</div>
					@endforeach
				</div>
			</div>

			<hr>
			<h6 class="mb-3"><i class="bi bi-list-ol me-1"></i>Opciones a votar</h6>
			<div id="optionsContainer">
				<div class="input-group mb-2">
					<span class="input-group-text">1</span>
					<input type="text" name="options[]" class="form-control" placeholder="ej: Aprobar" required>
					<button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="bi bi-dash"></i></button>
				</div>
				<div class="input-group mb-2">
					<span class="input-group-text">2</span>
					<input type="text" name="options[]" class="form-control" placeholder="ej: Rechazar" required>
					<button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="bi bi-dash"></i></button>
				</div>
			</div>
			<button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addOption()"><i class="bi bi-plus"></i> Agregar opción</button>

			<hr>
			<div class="mb-3">
				<label class="form-label">Estado inicial</label>
				<select name="status" class="form-select" style="max-width:280px">
					<option value="draft" @selected(old('status') === 'draft')>Borrador (abrir manualmente después)</option>
					<option value="open" @selected(old('status') === 'open')>Abrir ahora (notifica a los involucrados)</option>
				</select>
			</div>

			<div class="d-flex gap-2">
				<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Crear votación</button>
				<a href="{{ route('assemblies.index') }}" class="btn btn-outline-secondary btn-action">Cancelar</a>
			</div>
		</form>
	</div>
</div>

<script>
function toggleTowers() {
	document.getElementById('towerSelectDiv').style.display = document.getElementById('scopeSelect').value === 'tower' ? '' : 'none';
}
function toggleQuorum() {
	document.getElementById('quorumValueDiv').style.display = document.getElementById('quorumSelect').value === 'none' ? 'none' : '';
}
function addOption() {
	const container = document.getElementById('optionsContainer');
	const count = container.children.length + 1;
	const div = document.createElement('div');
	div.className = 'input-group mb-2';
	div.innerHTML = `<span class="input-group-text">${count}</span><input type="text" name="options[]" class="form-control" placeholder="Opción ${count}"><button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="bi bi-dash"></i></button>`;
	container.appendChild(div);
}
function removeOption(btn) {
	const container = document.getElementById('optionsContainer');
	if (container.children.length > 2) {
		btn.closest('.input-group').remove();
	} else {
		alert('Debe haber al menos 2 opciones.');
	}
}
toggleTowers();
toggleQuorum();
</script>
@endsection