@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<div>
		<h1><i class="bi bi-arrow-left-right me-2"></i>Transferencia entre Cuentas</h1>
	</div>
	<a class="btn btn-outline-secondary btn-action" href="{{ route('accounts.index') }}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card">
	<div class="card-body p-4">
		<form method="POST" action="{{route('accounts.movements.transfer.store')}}">
			@csrf
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label">Cuenta Origen</label>
					<select name="from_id" class="form-select" required>
						@foreach($accounts as $a)
							<option value="{{$a->id}}">{{$a->name}} (USD {{number_format($a->balance_usd,2)}} / VES {{number_format($a->balance_ves,2)}})</option>
						@endforeach
					</select>
				</div>
				<div class="col-md-6">
					<label class="form-label">Cuenta Destino</label>
					<select name="to_id" class="form-select" required>
						@foreach($accounts as $a)
							<option value="{{$a->id}}">{{$a->name}}</option>
						@endforeach
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label">Monto USD</label>
					<input type="number" step="0.01" name="amount_usd" class="form-control" value="0">
				</div>
				<div class="col-md-4">
					<label class="form-label">Monto VES</label>
					<input type="number" step="0.01" name="amount_ves" class="form-control" value="0">
				</div>
				<div class="col-md-4">
					<label class="form-label">Referencia</label>
					<input name="reference" class="form-control">
				</div>
				<div class="col-12">
					<button class="btn btn-primary btn-action"><i class="bi bi-arrow-left-right me-1"></i> Transferir</button>
				</div>
			</div>
		</form>
	</div>
</div>
@endsection
