@extends('layouts.app')
@section('content')<h1 class="title">Crear Condominio</h1><form method="POST" action="{{route('condominiums.store')}}">@csrf<div class="field"><label class="label">Nombre</label><div class="control"><input name="name" class="input" required></div></div><button class="button is-primary">Guardar</button></form>@endsection
