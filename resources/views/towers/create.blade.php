@extends('layouts.app')
@section('content')
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h4 m-0">Nueva Torre</h1>
		<a class="btn btn-secondary" href="{{route('towers.index')}}">Volver</a>
	</div>
	<form method="POST" action="{{route('towers.store')}}" class="card p-4 shadow-sm">
		@csrf
		@if($errors->any())
			<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>
		@endif
		<div class="mb-3">
			<label class="form-label">Nombre</label>
			<input name="name" class="form-control" required>
		</div>
		<div class="form-check mb-3">
			<input type="hidden" name="active" value="0">
			<input type="checkbox" name="active" value="1" class="form-check-input" id="activeCheck" checked>
			<label class="form-check-label" for="activeCheck">Activa</label>
		</div>
		<button class="btn btn-primary">Guardar</button>
	</form>
</div>
@endsection
