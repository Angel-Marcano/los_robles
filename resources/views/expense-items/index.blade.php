@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-receipt me-2"></i>Gastos / Items de Cobro</h1>
	</div>
	<a href="{{route('expense-items.create')}}" class="btn btn-primary btn-action"><i class="bi bi-plus-lg"></i> Nuevo Item</a>
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
				@forelse($items as $i)
				<tr>
					<td class="text-muted">{{$i->id}}</td>
					<td class="fw-semibold">{{$i->name}}</td>
					<td>
						@if($i->active)
							<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
						@else
							<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactivo</span>
						@endif
					</td>
					<td class="text-end">
						<a href="{{route('expense-items.edit',$i)}}" class="btn btn-sm btn-outline-primary btn-action me-1"><i class="bi bi-pencil"></i> Editar</a>
						<form method="POST" action="{{route('expense-items.destroy',$i)}}" style="display:inline">
							@csrf @method('DELETE')
							<button onclick="return confirm('¿Eliminar este gasto?')" class="btn btn-sm btn-outline-danger btn-action"><i class="bi bi-trash"></i> Borrar</button>
						</form>
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="4">
						<div class="empty-state">
							<i class="bi bi-receipt"></i>
							<p>No hay gastos configurados aún</p>
						</div>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

@if($items->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
	<div class="text-muted small">Mostrando {{ $items->firstItem() }}–{{ $items->lastItem() }} de {{ $items->total() }}</div>
	{{ $items->links() }}
</div>
@endif
@endsection
