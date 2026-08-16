@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-buildings me-2"></i>{{$condominium->name}}</h1>
	<a class="btn btn-outline-secondary btn-action" href="{{route('condominiums.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
<div class="card">
	<div class="card-body">
		<table class="table table-borderless">
			<tr>
				<th style="width:160px">ID</th>
				<td>{{$condominium->id}}</td>
			</tr>
			<tr>
				<th>Dominio</th>
				<td>{{$condominium->subdomain ?? '—'}}</td>
			</tr>
			<tr>
				<th>Base de datos</th>
				<td><code>{{$condominium->db_name ?? '—'}}</code></td>
			</tr>
			<tr>
				<th>Estado</th>
				<td>
					@if($condominium->active)
						<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
					@else
						<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactivo</span>
					@endif
				</td>
			</tr>
			<tr>
				<th>Reserva (%)</th>
				<td>{{number_format($condominium->reserve_percent ?? 0, 2)}}%</td>
			</tr>
		</table>
		<div class="mt-3">
			<a class="btn btn-outline-primary btn-action" href="{{route('condominiums.edit',$condominium)}}"><i class="bi bi-pencil"></i> Editar</a>
		</div>
	</div>
</div>
@endsection
