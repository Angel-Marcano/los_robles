@extends('layouts.app')
@section('content')
<h1 class="title">Restablecer contraseña</h1>
<form method="POST" action="{{url('password/reset')}}">@csrf
  <input type="hidden" name="token" value="{{$token}}">
  <div class="field"><label class="label">Nueva contraseña</label><div class="control"><input type="password" name="password" class="input" required></div></div>
  <button class="button is-primary">Actualizar</button>
</form>
@endsection
