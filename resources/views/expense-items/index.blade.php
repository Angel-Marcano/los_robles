@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 m-0">Gastos / Items de Cobro</h1>
        <a href="{{route('expense-items.create')}}" class="btn btn-primary">Nuevo Item</a>
    </div>
    @if(session('status'))
        <div class="alert alert-success">{{session('status')}}</div>
    @endif
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i)
                <tr>
                    <td>{{$i->id}}</td>
                    <td>{{$i->name}}</td>
                    <td>
                        <span class="badge {{ $i->active ? 'bg-success' : 'bg-secondary' }}">{{ $i->active ? 'Sí' : 'No' }}</span>
                    </td>
                    <td>
                        <a href="{{route('expense-items.edit',$i)}}" class="btn btn-sm btn-outline-primary me-1">Editar</a>
                        <form method="POST" action="{{route('expense-items.destroy',$i)}}" style="display:inline">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('Eliminar?')" class="btn btn-sm btn-outline-danger">Borrar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">No hay gastos configurados aún.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{$items->links()}}
    </div>
</div>
@endsection
