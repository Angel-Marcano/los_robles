@php
    $user = auth()->user();
    $isAdmin = $user->hasAnyRole(['super_admin', 'condo_admin', 'tower_admin']);
    $comments = $entity->comments()->where(function ($q) use ($isAdmin) {
        if ($isAdmin) {
            return $q;
        }
        return $q->where('is_internal', false);
    })->with('user')->get();
@endphp

<div class="card mt-3" id="comments-section">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-chat-dots me-1"></i>Consultas privadas</span>
        <span class="text-muted small">Solo tú y la administración pueden ver esto</span>
    </div>
    <div class="card-body">
        @if($comments->count())
            <div class="mb-3" style="max-height: 320px; overflow-y: auto;">
                @foreach($comments as $comment)
                    <div class="d-flex mb-3 {{ $comment->user_id === $user->id ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="p-2 rounded {{ $comment->user_id === $user->id ? 'bg-primary text-white' : 'bg-light border' }}" style="max-width: 75%;">
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <strong>{{ $comment->user->name ?? 'Usuario' }}</strong>
                                @if($comment->is_internal)
                                    <span class="badge bg-warning text-dark ms-2">Interno</span>
                                @endif
                            </div>
                            <div>{{ $comment->message }}</div>
                            <div class="small {{ $comment->user_id === $user->id ? 'text-white-50' : 'text-muted' }} mt-1">
                                {{ $comment->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted">No hay consultas aún. Escribe una duda sobre esta factura o pago.</p>
        @endif

        @can('comment', $entity)
            <form method="POST" action="{{ $storeRoute }}" class="mt-3">
                @csrf
                <div class="mb-2">
                    <textarea name="message" class="form-control" rows="3" placeholder="Escribe tu consulta..." required maxlength="2000"></textarea>
                </div>
                @if($isAdmin)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="is_internal_{{ $entity->id }}">
                        <label class="form-check-label" for="is_internal_{{ $entity->id }}">
                            Nota interna (solo administradores)
                        </label>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Enviar comentario</button>
            </form>
        @else
            <p class="text-muted small mb-0">No tienes permiso para comentar en este elemento.</p>
        @endcan
    </div>
</div>
