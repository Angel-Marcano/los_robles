@extends('layouts.app')
@section('content')
<h1 class="title">Propietarios / Inquilinos Apto {{$apartment->code}}</h1>
@if(session('status'))<div class="notification is-success">{{session('status')}}</div>@endif
<form method="POST" action="{{route('ownerships.store',$apartment)}}" class="box">@csrf
  <div class="field"><label class="label">Usuario</label><div class="control"><div class="select"><select name="user_id" required>
    @foreach($users as $u)
      <option value="{{$u->id}}">{{$u->first_name}} {{$u->last_name}} ({{$u->document_type}} {{$u->document_number}})</option>
    @endforeach
  </select></div></div></div>
  <div class="field"><label class="label">Rol</label><div class="control"><div class="select"><select name="role" required><option value="owner">Propietario</option><option value="co_owner">Co-Propietario</option><option value="tenant">Inquilino</option></select></div></div></div>
  <button class="button is-primary">Agregar</button>
</form>
<table class="table is-fullwidth is-striped">
  <thead><tr><th>Nombre</th><th>Documento</th><th>Rol</th><th>Activo</th><th></th></tr></thead>
  <tbody>
    @foreach($owners as $o)
      <tr>
        <td>{{$o->user->first_name}} {{$o->user->last_name}}</td>
        <td>{{$o->user->document_type}} {{$o->user->document_number}}</td>
        <td>{{$o->role}}</td>
        <td>{{$o->active?'Sí':'No'}}</td>
        <td class="is-narrow">
          <form method="POST" action="{{route('ownerships.toggle',[$apartment,$o])}}" style="display:inline">@csrf @method('PATCH')<button class="button is-small">{{$o->active?'Desactivar':'Activar'}}</button></form>
          <form method="POST" action="{{route('ownerships.destroy',[$apartment,$o])}}" style="display:inline">@csrf @method('DELETE')<button onclick="return confirm('Eliminar?')" class="button is-small is-danger">Eliminar</button></form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endsection
