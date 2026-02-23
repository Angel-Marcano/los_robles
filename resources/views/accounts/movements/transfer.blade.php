@extends('layouts.app')
@section('content')
<h1 class="title">Transferencia entre Cuentas</h1>
@if($errors->any())<div class="notification is-danger">{{$errors->first()}}</div>@endif
<form method="POST" action="{{route('accounts.movements.transfer.store')}}">@csrf
  <div class="field"><label class="label">Cuenta Origen</label><div class="control"><div class="select"><select name="from_id" required>@foreach($accounts as $a)<option value="{{$a->id}}">{{$a->name}} (USD {{$a->balance_usd}} / VES {{$a->balance_ves}})</option>@endforeach</select></div></div></div>
  <div class="field"><label class="label">Cuenta Destino</label><div class="control"><div class="select"><select name="to_id" required>@foreach($accounts as $a)<option value="{{$a->id}}">{{$a->name}}</option>@endforeach</select></div></div></div>
  <div class="field"><label class="label">Monto USD</label><div class="control"><input type="number" step="0.01" name="amount_usd" class="input" value="0"></div></div>
  <div class="field"><label class="label">Monto VES</label><div class="control"><input type="number" step="0.01" name="amount_ves" class="input" value="0"></div></div>
  <div class="field"><label class="label">Referencia</label><div class="control"><input name="reference" class="input"></div></div>
  <button class="button is-primary">Transferir</button>
</form>
@endsection
