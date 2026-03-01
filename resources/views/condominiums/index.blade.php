@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-buildings me-2"></i>Condominios</h1>
	<a class="btn btn-primary btn-action" href="{{route('condominiums.create')}}"><i class="bi bi-plus-lg"></i> Nuevo</a>
</div>
<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr><th>ID</th><th>Nombre</th><th>Activo</th><th></th></tr>
			</thead>
			<tbody>
				@forelse($items as $c)
				<tr>
					<td class="text-muted">{{$c->id}}</td>
					<td><a href="{{route('condominiums.show',$c)}}" class="fw-semibold text-decoration-none">{{$c->name}}</a></td>
					<td><span class="badge {{$c->active?'bg-success':'bg-secondary'}}">{{$c->active?'Sí':'No'}}</span></td>
					<td class="text-end">
						<a class="btn btn-sm btn-outline-primary btn-action" href="{{route('condominiums.edit',$c)}}"><i class="bi bi-pencil"></i> Editar</a>
						<form style="display:inline" method="POST" action="{{route('condominiums.destroy',$c)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('Eliminar?')"><i class="bi bi-trash"></i> Borrar</button></form>
					</td>
				</tr>
				@empty
				<tr><td colspan="4"><div class="empty-state"><i class="bi bi-buildings"></i><p>Sin condominios</p></div></td></tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
@if($items->hasPages())
<div class="mt-3">{{$items->links()}}</div>
@endif
@endsection
