@extends('layouts.app')
@section('content')
<div class="container">
  <h1 class="mb-4">Nuevo Item de Cobro</h1>
  <form method="POST" action="{{route('expense-items.store')}}" class="card p-4 shadow-sm">
    @csrf
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach
        </ul>
      </div>
    @endif
    <!-- En contexto tenant no se selecciona condominio -->
    <div class="mb-3">
      <label class="form-label">Nombre</label>
      <input class="form-control" name="name" required maxlength="120">
    </div>
    <!-- Sólo nombre y estado activo -->
    <div class="form-check mb-3">
      <input type="hidden" name="active" value="0">
      <input type="checkbox" name="active" value="1" class="form-check-input" id="activeCheck" {{old('active',1)?'checked':''}}>
      <label class="form-check-label" for="activeCheck">Activo</label>
    </div>
    <button class="btn btn-primary">Guardar</button>
  </form>
</div>
@endsection
