@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-people me-2"></i>Usuarios</h1>
	</div>
	<a class="btn btn-primary btn-action" href="{{ route('users.create') }}">
		<i class="bi bi-plus-lg"></i> Nuevo Usuario
	</a>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>Nombre</th>
					<th>Email</th>
					<th>Estado</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($users as $u)
				<tr>
					<td class="text-muted">{{ $u->id }}</td>
					<td class="fw-semibold">{{ $u->name }}</td>
					<td>{{ $u->email }}</td>
					<td>
						@if($u->active)
							<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
						@else
							<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactivo</span>
						@endif
					</td>
					<td class="text-end">
						<a class="btn btn-sm btn-outline-primary btn-action me-1" href="{{ route('users.edit', $u) }}">
							<i class="bi bi-pencil"></i> Editar
						</a>
						<form style="display:inline" method="POST" action="{{ route('users.toggle', $u) }}">@csrf @method('PATCH')
							@if($u->active)
								<button class="btn btn-sm btn-outline-warning btn-action me-1"><i class="bi bi-pause-circle"></i> Desactivar</button>
							@else
								<button class="btn btn-sm btn-outline-success btn-action me-1"><i class="bi bi-play-circle"></i> Activar</button>
							@endif
						</form>
						<form style="display:inline" method="POST" action="{{ route('users.destroy', $u) }}">@csrf @method('DELETE')
							<button class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('¿Eliminar este usuario?')">
								<i class="bi bi-trash"></i> Borrar
							</button>
						</form>
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="5">
						<div class="empty-state">
							<i class="bi bi-people"></i>
							<p>No hay usuarios registrados</p>
						</div>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

@if($users->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
	<div class="text-muted small">Mostrando {{ $users->firstItem() }}–{{ $users->lastItem() }} de {{ $users->total() }}</div>
	{{ $users->links() }}
</div>
@endif
@endsection
