@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-bell me-2"></i>Notificaciones</h1>
	@if($notifications->where('read', false)->isNotEmpty())
		<form method="POST" action="{{ route('notifications.readAll') }}">@csrf
			<button class="btn btn-sm btn-outline-secondary">Marcar todas leídas</button>
		</form>
	@endif
</div>
<div class="card">
	<div class="list-group list-group-flush">
		@forelse($notifications as $n)
			<div class="list-group-item {{ $n->read ? '' : 'fw-semibold bg-light' }}">
				<div class="d-flex gap-3 align-items-start">
					<i class="bi {{ $n->iconClass() }} fs-5 mt-1"></i>
					<div class="flex-grow-1">
						<div>{{ $n->title }}</div>
						@if($n->body)
							<div class="text-muted small">{{ $n->body }}</div>
						@endif
						<div class="text-muted" style="font-size:0.75rem">{{ $n->created_at->diffForHumans() }}</div>
					</div>
					<div class="d-flex gap-1">
						@if($n->url)
							<a href="{{ $n->url }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
						@endif
						@if(!$n->read)
							<form method="POST" action="{{ route('notifications.read', $n) }}">@csrf @method('PATCH')
								<button class="btn btn-sm btn-outline-success"><i class="bi bi-check"></i></button>
							</form>
						@endif
					</div>
				</div>
			</div>
		@empty
			<div class="text-center py-5">
				<i class="bi bi-bell-slash fs-1 text-muted"></i>
				<p class="text-muted mt-2">No tienes notificaciones.</p>
			</div>
		@endforelse
	</div>
</div>
@if($notifications->hasPages())
<div class="mt-3">{{ $notifications->links() }}</div>
@endif
@endsection