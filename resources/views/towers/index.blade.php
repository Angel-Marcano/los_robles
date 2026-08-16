@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-building me-2"></i>Torres</h1>
	</div>
	<a class="btn btn-primary btn-action" href="{{route('towers.create')}}">
		<i class="bi bi-plus-lg"></i> Nueva Torre
	</a>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>Nombre</th>
					<th>Estado</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($towers as $t)
				<tr>
					<td class="text-muted">{{$t->id}}</td>
					<td class="fw-semibold">{{$t->name}}</td>
					<td>
						@if($t->active)
							<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
						@else
							<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactivo</span>
						@endif
					</td>
					<td class="text-end">
						<a class="btn btn-sm btn-outline-info btn-action me-1" href="{{route('towers.apartments.index',$t)}}">
							<i class="bi bi-door-open"></i> Apartamentos
						</a>
						<a class="btn btn-sm btn-outline-primary btn-action me-1" href="{{route('towers.edit',$t)}}">
							<i class="bi bi-pencil"></i> Editar
						</a>
						<form style="display:inline" method="POST" action="{{route('towers.destroy',$t)}}">
							@csrf @method('DELETE')
							<button class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('¿Eliminar esta torre?')">
								<i class="bi bi-trash"></i> Borrar
							</button>
						</form>
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="4">
						<div class="empty-state">
							<i class="bi bi-building"></i>
							<p>No hay torres registradas</p>
						</div>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

@if($towers->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
	<div class="text-muted small">Mostrando {{ $towers->firstItem() }}–{{ $towers->lastItem() }} de {{ $towers->total() }}</div>
	{{ $towers->links() }}
</div>
@endif
@endsection
