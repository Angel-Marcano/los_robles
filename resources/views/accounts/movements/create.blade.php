@extends('layouts.app')
@section('content')
<div class="container">
  <h1 class="mb-4">Movimiento Cuenta {{$account->name}}</h1>
  <form method="POST" action="{{route('accounts.movements.store',$account)}}" class="card p-4 shadow-sm">
    @csrf
    <div class="mb-3">
      <label class="form-label">Tipo</label>
      <select name="type" class="form-select" required>
        <option value="deposit">Depósito</option>
        <option value="withdraw">Retiro</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Monto USD</label>
      <input type="number" step="0.01" name="amount_usd" class="form-control" value="0">
    </div>
    <div class="mb-3">
      <label class="form-label">Monto VES</label>
      <input type="number" step="0.01" name="amount_ves" class="form-control" value="0">
    </div>
    <div class="mb-3">
      <label class="form-label">Referencia</label>
      <input name="reference" class="form-control">
    </div>
    <button class="btn btn-primary">Registrar</button>
  </form>
</div>
@endsection
