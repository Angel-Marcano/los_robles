@extends('layouts.app')
@section('content')
<div class="row justify-content-center" style="min-height: 60vh; align-items: center;">
	<div class="col-md-5 col-lg-4">
		<div class="card">
			<div class="card-body p-4">
				<h3 class="fw-bold mb-3"><i class="bi bi-key me-2"></i>Recuperar contraseña</h3>
				@if(session('status'))
					<div class="alert alert-success">{{ session('status') }}</div>
				@endif
				@if($errors->any())
					<div class="alert alert-danger">{{ $errors->first() }}</div>
				@endif
				<form method="POST" action="{{url('password/forgot')}}">
					@csrf
					<div class="mb-3">
						<label class="form-label">Email</label>
						<input type="email" name="email" class="form-control" required placeholder="tu@email.com">
					</div>
					<button class="btn btn-primary w-100 btn-action"><i class="bi bi-send me-1"></i> Enviar enlace</button>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection
