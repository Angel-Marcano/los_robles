@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
  <h1><i class="bi bi-currency-exchange me-2"></i>Cambio de Divisas</h1>
  <a class="btn btn-outline-secondary btn-action" href="{{route('accounts.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
  @if($errors->any())
    <div class="alert alert-danger">{{$errors->first()}}</div>
  @endif
  <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-info-circle fs-5"></i>
    <span><strong>Tasa activa:</strong> <span class="badge bg-info text-dark">{{$rate? $rate->rate:'N/D'}} USD→VES</span></span>
  </div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{route('exchange.store')}}">
      @csrf
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Cuenta Origen</label>
          <select name="origin_id" class="form-select" required>
            @foreach($accounts as $a)
              <option value="{{$a->id}}">{{$a->name}} (USD {{number_format($a->balance_usd,2)}} / VES {{number_format($a->balance_ves,2)}})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cuenta Destino</label>
          <select name="target_id" class="form-select" required>
            @foreach($accounts as $a)
              <option value="{{$a->id}}">{{$a->name}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Dirección</label>
          <select name="direction" class="form-select" required>
            <option value="usd_to_ves">USD → VES</option>
            <option value="ves_to_usd">VES → USD</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Monto Origen</label>
          <input type="number" step="0.01" name="amount" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tasa de cambio</label>
          <input type="number" step="0.0001" name="rate" class="form-control" value="{{ $rate ? $rate->rate : '' }}" required>
          <div class="form-text">Puedes ajustar la tasa manualmente</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Referencia</label>
          <input name="reference" class="form-control">
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary btn-action"><i class="bi bi-arrow-left-right"></i> Convertir</button>
      </div>
    </form>
  </div>
</div>
@endsection
