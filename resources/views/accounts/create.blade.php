@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
  <h1><i class="bi bi-wallet2 me-2"></i>Nueva Cuenta</h1>
  <a class="btn btn-outline-secondary btn-action" href="{{route('accounts.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{route('accounts.store')}}">
      @csrf
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Nombre</label>
          <input name="name" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Balance Inicial USD</label>
          <input type="number" step="0.01" name="balance_usd" class="form-control" value="0">
        </div>
        <div class="col-md-6">
          <label class="form-label">Balance Inicial VES</label>
          <input type="number" step="0.01" name="balance_ves" class="form-control" value="0">
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection
