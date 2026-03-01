@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
  <h1><i class="bi bi-arrow-left-right me-2"></i>Movimiento — {{$account->name}}</h1>
  <a class="btn btn-outline-secondary btn-action" href="{{route('accounts.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{route('accounts.movements.store',$account)}}">
      @csrf
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Tipo</label>
          <select name="type" class="form-select" required>
            <option value="deposit">Depósito</option>
            <option value="withdraw">Retiro</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Referencia</label>
          <input name="reference" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Monto USD</label>
          <input type="number" step="0.01" name="amount_usd" class="form-control" value="0">
        </div>
        <div class="col-md-6">
          <label class="form-label">Monto VES</label>
          <input type="number" step="0.01" name="amount_ves" class="form-control" value="0">
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Registrar</button>
      </div>
    </form>
  </div>
</div>
@endsection
