@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
	<h1 class="h3">Usuarios</h1>
	<a class="btn btn-primary" href="{{ route('users.create') }}">Nuevo Usuario</a>
</div>

<div class="table-responsive">
	<table class="table table-striped table-hover align-middle">
		<thead>
			<tr>
				<th scope="col">ID</th>
				<th scope="col">Nombre</th>
				<th scope="col">Email</th>
				<th scope="col">Activo</th>
				<th scope="col">Acciones</th>
			</tr>
		</thead>
		<tbody>
			@foreach($users as $u)
			<tr>
				<td>{{ $u->id }}</td>
				<td>{{ $u->name }}</td>
				<td>{{ $u->email }}</td>
				<td>{{ $u->active ? 'Sí' : 'No' }}</td>
				<td>
					<a class="btn btn-sm btn-outline-primary me-1" href="{{ route('users.edit', $u) }}">Editar</a>
					<form style="display:inline" method="POST" action="{{ route('users.toggle', $u) }}">@csrf @method('PATCH')
						<button class="btn btn-sm btn-warning me-1">{{ $u->active ? 'Desactivar' : 'Activar' }}</button>
					</form>
					<form style="display:inline" method="POST" action="{{ route('users.destroy', $u) }}">@csrf @method('DELETE')
						<button class="btn btn-sm btn-danger" onclick="return confirm('Eliminar?')">Borrar</button>
					</form>
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>
</div>

<div class="d-flex justify-content-between align-items-center">
	<div class="text-muted small">Mostrando {{ $users->firstItem() }} - {{ $users->lastItem() }} de {{ $users->total() }}</div>
	{{ $users->links() }}
</div>

@endsection
