@extends('layouts.app')
@section('content')
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="mb-0">Tasas de Cambio</h1>
		<a class="btn btn-primary" href="{{route('rates.create')}}">Nueva Tasa</a>
	</div>
	<div class="table-responsive">
		<table class="table table-striped table-bordered">
			<thead class="table-light">
				<tr>
					<th>ID</th>
					<th>Base</th>
					<th>Quote</th>
					<th>Tasa</th>
					<th>Desde</th>
					<th>Activa</th>
				</tr>
			</thead>
			<tbody>
				@foreach($rates as $r)
				<tr>
					<td>{{$r->id}}</td>
					<td>{{$r->base}}</td>
					<td>{{$r->quote}}</td>
					<td>{{$r->rate}}</td>
					<td>{{$r->valid_from}}</td>
					<td>
						<span class="badge {{ $r->active ? 'bg-success' : 'bg-secondary' }}">{{ $r->active ? 'Sí' : 'No' }}</span>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	<div class="mt-3">
		{{$rates->links()}}
	</div>
</div>
@endsection
