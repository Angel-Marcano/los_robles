@extends('layouts.app')
@section('content')
<div class="container">
  <h1 class="mb-4">Cambio de Divisas</h1>
  @if($errors->any())
    <div class="alert alert-danger">{{$errors->first()}}</div>
  @endif
  <div class="mb-3">
    <span class="fw-bold">Tasa activa:</span> <span class="badge bg-info text-dark">{{$rate? $rate->rate:'N/D'}} USD→VES</span>
  </div>
  <form method="POST" action="{{route('exchange.store')}}" class="card p-4 shadow-sm">
    @csrf
    <div class="mb-3">
      <label class="form-label">Cuenta Origen</label>
      <select name="origin_id" class="form-select" required>
        @foreach($accounts as $a)
          <option value="{{$a->id}}">{{$a->name}} (USD {{$a->balance_usd}} / VES {{$a->balance_ves}})</option>
        @endforeach
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Cuenta Destino</label>
      <select name="target_id" class="form-select" required>
        @foreach($accounts as $a)
          <option value="{{$a->id}}">{{$a->name}}</option>
        @endforeach
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Dirección</label>
      <select name="direction" class="form-select" required>
        <option value="usd_to_ves">USD → VES</option>
        <option value="ves_to_usd">VES → USD</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Monto Origen</label>
      <input type="number" step="0.01" name="amount" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Referencia</label>
      <input name="reference" class="form-control">
    </div>
    <button class="btn btn-primary">Convertir</button>
  </form>
</div>
@endsection
