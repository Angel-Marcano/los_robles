@extends('layouts.app')
@section('content')
<h1 class="title">Olvidé mi contraseña</h1>
@if(session('status'))<div class="notification is-info">{{session('status')}}</div>@endif
<form method="POST" action="{{url('password/forgot')}}">@csrf
  <div class="field"><label class="label">Email</label><div class="control"><input type="email" name="email" class="input" required></div></div>
  <button class="button is-primary">Enviar enlace</button>
</form>
@endsection
