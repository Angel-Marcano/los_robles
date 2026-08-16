@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
  <h1><i class="bi bi-pencil-square me-2"></i>Editar Item #{{$item->id}}</h1>
  <a class="btn btn-outline-secondary btn-action" href="{{route('expense-items.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{route('expense-items.update',$item)}}">
      @csrf
      @method('PUT')
      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach
          </ul>
        </div>
      @endif
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" name="name" required maxlength="120" value="{{$item->name}}">
      </div>
      <div class="form-check mb-3">
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" value="1" class="form-check-input" id="activeCheckEdit" {{$item->active?'checked':''}}>
        <label class="form-check-label" for="activeCheckEdit">Activo</label>
      </div>
      <button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Actualizar</button>
    </form>
  </div>
</div>
@endsection
