@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
  <h1><i class="bi bi-plus-circle me-2"></i>Nuevo Item de Cobro</h1>
  <a class="btn btn-outline-secondary btn-action" href="{{route('expense-items.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{route('expense-items.store')}}">
      @csrf
      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach
          </ul>
        </div>
      @endif
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" name="name" required maxlength="120">
      </div>
      <div class="form-check mb-3">
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" value="1" class="form-check-input" id="activeCheck" {{old('active',1)?'checked':''}}>
        <label class="form-check-label" for="activeCheck">Activo</label>
      </div>
      <button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar</button>
    </form>
  </div>
</div>
@endsection
