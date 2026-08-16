@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-people me-2"></i>Propietarios — Apto {{$apartment->code}}</h1>
	</div>
</div>

<div class="card mb-4">
	<div class="card-body">
		<h6 class="fw-bold mb-3">Agregar propietario</h6>
		<form method="POST" action="{{route('ownerships.store',$apartment)}}" class="row g-3 align-items-end">
			@csrf
			<div class="col-md-5">
				<label class="form-label">Usuario</label>
				<select name="user_id" class="form-select" required>
					@foreach($users as $u)
						<option value="{{$u->id}}">{{$u->first_name}} {{$u->last_name}} ({{$u->document_type}} {{$u->document_number}})</option>
					@endforeach
				</select>
			</div>
			<div class="col-md-4">
				<label class="form-label">Rol</label>
				<select name="role" class="form-select" required>
					<option value="owner">Propietario</option>
					<option value="co_owner">Co-Propietario</option>
					<option value="tenant">Inquilino</option>
				</select>
			</div>
			<div class="col-md-3">
				<button class="btn btn-primary btn-action w-100"><i class="bi bi-plus-lg"></i> Agregar</button>
			</div>
		</form>
	</div>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Nombre</th>
					<th>Documento</th>
					<th>Rol</th>
					<th>Estado</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($owners as $o)
					<tr>
						<td class="fw-semibold">{{$o->user->first_name}} {{$o->user->last_name}}</td>
						<td>{{$o->user->document_type}} {{$o->user->document_number}}</td>
						<td><span class="badge bg-info text-dark">{{$o->role}}</span></td>
						<td>
							@if($o->active)
								<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
							@else
								<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactivo</span>
							@endif
						</td>
						<td class="text-end">
							<form method="POST" action="{{route('ownerships.toggle',[$apartment,$o])}}" style="display:inline">@csrf @method('PATCH')
								<button class="btn btn-sm {{ $o->active ? 'btn-outline-warning' : 'btn-outline-success' }} btn-action me-1">
									<i class="bi {{ $o->active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i> {{$o->active?'Desactivar':'Activar'}}
								</button>
							</form>
							<form method="POST" action="{{route('ownerships.destroy',[$apartment,$o])}}" style="display:inline">@csrf @method('DELETE')
								<button onclick="return confirm('¿Eliminar?')" class="btn btn-sm btn-outline-danger btn-action"><i class="bi bi-trash"></i> Eliminar</button>
							</form>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="5">
							<div class="empty-state">
								<i class="bi bi-people"></i>
								<p>Sin propietarios asignados</p>
							</div>
						</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
@endsection
