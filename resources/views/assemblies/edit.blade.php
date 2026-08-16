@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-pencil-square me-2"></i>Editar Votación</h1>
	<a href="{{ route('assemblies.show', $assembly) }}" class="btn btn-outline-secondary btn-action"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card">
	<div class="card-body">
		<form method="POST" action="{{ route('assemblies.update', $assembly) }}">
			@csrf @method('PATCH')
			<div class="mb-3">
				<label class="form-label">Título</label>
				<input type="text" name="title" class="form-control" value="{{ old('title', $assembly->title) }}" required>
			</div>
			<div class="mb-3">
				<label class="form-label">Descripción</label>
				<textarea name="description" class="form-control" rows="3">{{ old('description', $assembly->description) }}</textarea>
			</div>
			<div class="row g-3 mb-3">
				<div class="col-md-4">
					<label class="form-label">Alcance</label>
					<select name="scope" class="form-select" id="scopeSelect" onchange="toggleTowers()">
						<option value="condo" @selected(old('scope', $assembly->scope) === 'condo')>Condominio completo</option>
						<option value="tower" @selected(old('scope', $assembly->scope) === 'tower')>Torre(s) específica(s)</option>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label">Tipo de voto</label>
					<select name="vote_type" class="form-select">
						<option value="public" @selected(old('vote_type', $assembly->vote_type) === 'public')>Público</option>
						<option value="secret" @selected(old('vote_type', $assembly->vote_type) === 'secret')>Oculto</option>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label">Peso</label>
					<select name="weight_mode" class="form-select">
						<option value="equal" @selected(old('weight_mode', $assembly->weight_mode) === 'equal')>1 voto por apto</option>
						<option value="aliquot" @selected(old('weight_mode', $assembly->weight_mode) === 'aliquot')>Por alícuota</option>
					</select>
				</div>
			</div>
			<div class="row g-3 mb-3">
				<div class="col-md-4">
					<label class="form-label">Quórum</label>
					<select name="quorum_type" class="form-select" id="quorumSelect" onchange="toggleQuorum()">
						<option value="none" @selected(old('quorum_type', $assembly->quorum_type) === 'none')>Sin límite</option>
						<option value="simple" @selected(old('quorum_type', $assembly->quorum_type) === 'simple')>Simple (50%+1)</option>
						<option value="qualified" @selected(old('quorum_type', $assembly->quorum_type) === 'qualified')>Calificada (2/3)</option>
					</select>
				</div>
				<div class="col-md-4" id="quorumValueDiv" style="display:none">
					<label class="form-label">Porcentaje mínimo</label>
					<input type="number" name="quorum_value" class="form-control" value="{{ old('quorum_value', $assembly->quorum_value) }}" min="1" max="100" step="0.01">
				</div>
				<div class="col-md-4">
					<label class="form-label">Cierre</label>
					<input type="datetime-local" name="closes_at" class="form-control" value="{{ old('closes_at', $assembly->closes_at?->format('Y-m-d\TH:i')) }}">
				</div>
			</div>
			<div id="towerSelectDiv" style="display:none" class="mb-3">
				<label class="form-label">Torres</label>
				<div class="d-flex flex-wrap gap-3">
					@foreach($towers as $tower)
					<div class="form-check">
						<input class="form-check-input" type="checkbox" name="tower_ids[]" value="{{ $tower->id }}" id="tw_{{ $tower->id }}" @checked(in_array($tower->id, old('tower_ids', $assembly->tower_ids ?? [])))>
						<label class="form-check-label" for="tw_{{ $tower->id }}">{{ $tower->name }}</label>
					</div>
					@endforeach
				</div>
			</div>
			<hr>
			<h6 class="mb-3">Opciones</h6>
			<div id="optionsContainer">
				@foreach($assembly->options as $i => $opt)
				<div class="input-group mb-2">
					<span class="input-group-text">{{ $i + 1 }}</span>
					<input type="text" name="options[]" class="form-control" value="{{ old('options.' . $i, $opt->label) }}" required>
					<button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="bi bi-dash"></i></button>
				</div>
				@endforeach
			</div>
			<button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addOption()"><i class="bi bi-plus"></i> Agregar</button>
			<div class="d-flex gap-2">
				<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar</button>
				<a href="{{ route('assemblies.show', $assembly) }}" class="btn btn-outline-secondary">Cancelar</a>
			</div>
		</form>
	</div>
</div>
<script>
function toggleTowers(){document.getElementById('towerSelectDiv').style.display=document.getElementById('scopeSelect').value==='tower'?'':'none';}
function toggleQuorum(){document.getElementById('quorumValueDiv').style.display=document.getElementById('quorumSelect').value==='none'?'none':'';}
function addOption(){const c=document.getElementById('optionsContainer');const n=c.children.length+1;const d=document.createElement('div');d.className='input-group mb-2';d.innerHTML=`<span class="input-group-text">${n}</span><input type="text" name="options[]" class="form-control" placeholder="Opción ${n}"><button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="bi bi-dash"></i></button>`;c.appendChild(d);}
function removeOption(b){const c=document.getElementById('optionsContainer');if(c.children.length>2){b.closest('.input-group').remove();}else{alert('Mínimo 2 opciones.');}}
toggleTowers();toggleQuorum();
</script>
@endsection