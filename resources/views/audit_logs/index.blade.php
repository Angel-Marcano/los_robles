@extends('layouts.app')
@section('content')
<h1 class="title">Auditoría</h1>
<form method="GET" class="box" style="margin-bottom:1rem;">
  <div class="columns is-multiline">
    <div class="column is-one-quarter">
      <label class="label">Entidad</label>
      <div class="select is-fullwidth"><select name="entity_type"><option value="">-- Todas --</option>@foreach($distinctTypes as $t)<option value="{{$t}}" @if(request('entity_type')===$t) selected @endif>{{$t}}</option>@endforeach</select></div>
    </div>
    <div class="column is-one-quarter">
      <label class="label">Acción</label>
      <div class="select is-fullwidth"><select name="action"><option value="">-- Todas --</option>@foreach($distinctActions as $a)<option value="{{$a}}" @if(request('action')===$a) selected @endif>{{$a}}</option>@endforeach</select></div>
    </div>
    <div class="column is-one-quarter">
      <label class="label">Usuario</label>
      <div class="select is-fullwidth"><select name="user_id"><option value="">-- Todos --</option>@foreach($users as $u)<option value="{{$u->id}}" @if(request('user_id')==$u->id) selected @endif>{{$u->name}}</option>@endforeach</select></div>
    </div>
    <div class="column is-one-quarter">
      <label class="label">Rango fechas</label>
      <div class="field is-grouped">
        <p class="control"><input type="date" name="date_from" value="{{request('date_from')}}" class="input" placeholder="Desde"></p>
        <p class="control"><input type="date" name="date_to" value="{{request('date_to')}}" class="input" placeholder="Hasta"></p>
      </div>
    </div>
    <div class="column is-one-quarter">
      <label class="label">Por página</label>
      <div class="select is-fullwidth"><select name="per_page"><option value="10" @if($perPage==10) selected @endif>10</option><option value="20" @if($perPage==20) selected @endif>20</option><option value="50" @if($perPage==50) selected @endif>50</option></select></div>
    </div>
    <div class="column is-full">
      <button class="button is-primary">Filtrar</button>
      <a href="{{url('audit-logs')}}" class="button is-light">Limpiar</a>
      <a href="{{url('audit-logs') . '?' . http_build_query(array_merge(request()->query(),['export'=>'csv']))}}" class="button is-link">Exportar CSV</a>
    </div>
  </div>
</form>
<table class="table is-fullwidth is-striped is-bordered">
  <thead><tr><th>ID</th><th>Fecha</th><th>Usuario</th><th>Entidad</th><th>Acción</th><th>Entidad ID</th><th>IP</th><th>Cambios</th></tr></thead>
  <tbody>
    @foreach($logs as $log)
      <tr>
        <td>{{$log->id}}</td>
        <td>{{$log->created_at}}</td>
        <td>{{$log->user_id}}</td>
        <td>{{$log->entity_type}}</td>
        <td>{{$log->action}}</td>
        <td>{{$log->entity_id}}</td>
        <td>{{$log->ip}}</td>
        <td><pre style="white-space:pre-wrap;font-size:10px;">{{ json_encode($log->changes,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></td>
      </tr>
    @endforeach
  </tbody>
</table>
<div>
  {{$logs->links()}}
</div>
@endsection
