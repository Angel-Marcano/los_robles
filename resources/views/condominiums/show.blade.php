@extends('layouts.app')
@section('content')<h1 class="title">{{$condominium->name}}</h1><a class="button" href="{{route('condominiums.towers.index',$condominium)}}">Torres</a>@endsection
