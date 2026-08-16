@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-wallet2 me-2"></i>Cuentas</h1>
	</div>
	<div class="d-flex gap-2">
		<a class="btn btn-outline-info btn-action" href="{{route('accounts.movements.transfer.form')}}"><i class="bi bi-arrow-left-right"></i> Transferir</a>
		<a class="btn btn-primary btn-action" href="{{route('accounts.create')}}"><i class="bi bi-plus-lg"></i> Nueva Cuenta</a>
	</div>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Nombre</th>
					<th class="text-end">USD</th>
					<th class="text-end">VES</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($accounts as $a)
				<tr>
					<td class="fw-semibold">{{$a->name}}</td>
					<td class="text-end font-monospace">{{ number_format($a->balance_usd, 2) }}</td>
					<td class="text-end font-monospace">{{ number_format($a->balance_ves, 2) }}</td>
					<td class="text-end">
						<a class="btn btn-sm btn-outline-primary btn-action me-1" href="{{route('accounts.edit',$a)}}"><i class="bi bi-pencil"></i> Editar</a>
						<a class="btn btn-sm btn-outline-info btn-action" href="{{route('accounts.movements.create',$a)}}"><i class="bi bi-plus-circle"></i> Movimiento</a>
					</td>
				</tr>
				@empty
				<tr>
					<td colspan="4">
						<div class="empty-state">
							<i class="bi bi-wallet2"></i>
							<p>No hay cuentas registradas</p>
						</div>
					</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

@if($accounts->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
	<div class="text-muted small">Mostrando {{ $accounts->firstItem() }}–{{ $accounts->lastItem() }} de {{ $accounts->total() }}</div>
	{{ $accounts->links() }}
</div>
@endif
@endsection
