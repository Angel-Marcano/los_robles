@extends('layouts.app')
@section('content')
<div class="container">
  <h1 class="mb-4">Nueva Cuenta</h1>
  <form method="POST" action="{{route('accounts.store')}}" class="card p-4 shadow-sm">
    @csrf
    <div class="mb-3">
      <label class="form-label">Nombre</label>
      <input name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Balance Inicial USD</label>
      <input type="number" step="0.01" name="balance_usd" class="form-control" value="0">
    </div>
    <div class="mb-3">
      <label class="form-label">Balance Inicial VES</label>
      <input type="number" step="0.01" name="balance_ves" class="form-control" value="0">
    </div>
    <button class="btn btn-primary">Guardar</button>
  </form>
</div>
@endsection
