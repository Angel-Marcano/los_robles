@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-buildings me-2"></i>{{$condominium->name}}</h1>
	<a class="btn btn-outline-primary btn-action" href="{{route('condominiums.towers.index',$condominium)}}"><i class="bi bi-building"></i> Torres</a>
</div>
@endsection
