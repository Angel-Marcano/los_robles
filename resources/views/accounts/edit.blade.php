@extends('layouts.app')
@section('content')
<div class="container">
  <h1 class="mb-4">Editar Cuenta</h1>
  <form method="POST" action="{{route('accounts.update',$account)}}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="mb-3">
      <label class="form-label">Nombre</label>
      <input name="name" class="form-control" value="{{$account->name}}" required>
    </div>
    <button class="btn btn-primary">Actualizar</button>
  </form>
</div>
@endsection
