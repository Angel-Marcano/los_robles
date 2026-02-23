@extends('layouts.app')
@section('content')
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h4 m-0">Torres</h1>
		<a class="btn btn-primary" href="{{route('towers.create')}}">Nueva Torre</a>
	</div>
	<div class="table-responsive">
		<table class="table table-striped table-bordered">
			<thead class="table-light"><tr><th>ID</th><th>Nombre</th><th>Activo</th><th>Acciones</th></tr></thead>
			<tbody>
				@foreach($towers as $t)
				<tr>
					<td>{{$t->id}}</td>
					<td><a href="{{route('towers.apartments.index',$t)}}">{{$t->name}}</a></td>
					<td><span class="badge {{ $t->active ? 'bg-success' : 'bg-secondary' }}">{{ $t->active ? 'Sí' : 'No' }}</span></td>
					<td>
						<a class="btn btn-sm btn-outline-primary me-1" href="{{route('towers.edit',$t)}}">Editar</a>
						<form style="display:inline" method="POST" action="{{route('towers.destroy',$t)}}">
							@csrf @method('DELETE')
							<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar?')">Borrar</button>
						</form>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	<div class="mt-3">{{$towers->links()}}</div>
</div>
@endsection
