@extends('layouts.app')
@section('content')
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h4 m-0">Apartamentos de la Torre {{ $tower->name }}</h1>
		<a class="btn btn-primary" href="{{ route('towers.apartments.create',$tower) }}">Nuevo Apartamento</a>
	</div>

	@if(session('status'))
		<div class="alert alert-success">{{ session('status') }}</div>
	@endif

	@if($apartments->count() === 0)
		<div class="alert alert-light">No hay apartamentos registrados en esta torre.</div>
	@else
		<div class="table-responsive">
			<table class="table table-striped table-bordered">
				<thead class="table-light">
					<tr>
						<th>ID</th>
						<th>Código</th>
						<th>Alícuota (%)</th>
						<th>Activo</th>
						<th>Acciones</th>
					</tr>
				</thead>
				<tbody>
					@foreach($apartments as $a)
						<tr>
							<td>{{ $a->id }}</td>
							<td>{{ $a->code }}</td>
							<td>{{ $a->aliquot_percent }}</td>
							<td>
								<span class="badge {{ $a->active ? 'bg-success' : 'bg-secondary' }}">{{ $a->active ? 'Sí' : 'No' }}</span>
							</td>
							<td>
								<a class="btn btn-sm btn-outline-primary me-1" href="{{ route('apartments.edit',$a) }}">Editar</a>
								<form style="display:inline" method="POST" action="{{ route('apartments.destroy',$a) }}">
									@csrf @method('DELETE')
									<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar?')">Borrar</button>
								</form>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		<div class="mt-3">{{ $apartments->links() }}</div>
	@endif
</div>
@endsection
