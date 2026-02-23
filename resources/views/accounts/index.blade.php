@extends('layouts.app')
@section('content')
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="mb-0">Cuentas</h1>
		<div>
			<a class="btn btn-primary me-2" href="{{route('accounts.create')}}">Nueva Cuenta</a>
			<a class="btn btn-info" href="{{route('accounts.movements.transfer.form')}}">Transferir</a>
		</div>
	</div>
	@if(session('status'))
		<div class="alert alert-success">{{session('status')}}</div>
	@endif
	<div class="table-responsive">
		<table class="table table-striped table-bordered">
			<thead class="table-light">
				<tr>
					<th>Nombre</th>
					<th>USD</th>
					<th>VES</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
				@foreach($accounts as $a)
				<tr>
					<td>{{$a->name}}</td>
					<td>{{$a->balance_usd}}</td>
					<td>{{$a->balance_ves}}</td>
					<td>
						<a class="btn btn-sm btn-outline-primary me-1" href="{{route('accounts.edit',$a)}}">Editar</a>
						<a class="btn btn-sm btn-outline-info" href="{{route('accounts.movements.create',$a)}}">Movimiento</a>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	<div class="mt-3">
		{{$accounts->links()}}
	</div>
</div>
@endsection
