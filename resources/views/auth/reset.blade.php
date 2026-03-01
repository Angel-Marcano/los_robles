@extends('layouts.app')
@section('content')
<div class="row justify-content-center" style="min-height: 60vh; align-items: center;">
	<div class="col-md-5 col-lg-4">
		<div class="card">
			<div class="card-body p-4">
				<h3 class="fw-bold mb-3"><i class="bi bi-key me-2"></i>Restablecer contraseña</h3>
				@if($errors->any())
					<div class="alert alert-danger">{{ $errors->first() }}</div>
				@endif
				<form method="POST" action="{{url('password/reset')}}">
					@csrf
					<input type="hidden" name="token" value="{{$token}}">
					<div class="mb-3">
						<label class="form-label">Nueva contraseña</label>
						<input type="password" name="password" class="form-control" required minlength="6">
					</div>
					<div class="mb-3">
						<label class="form-label">Confirmar contraseña</label>
						<input type="password" name="password_confirmation" class="form-control" required minlength="6">
					</div>
					<button class="btn btn-primary w-100 btn-action"><i class="bi bi-check-lg me-1"></i> Actualizar</button>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection
