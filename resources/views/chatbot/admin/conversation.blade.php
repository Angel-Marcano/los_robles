@extends('layouts.app')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-chat-left-text me-2"></i>Conversación #{{ $conversation->id }}</h1>
        <p class="text-muted mb-0">
            Usuario: <strong>{{ $conversation->user?->name ?? 'Anónimo' }}</strong>
            · Sesión: <code>{{ Str::limit($conversation->session_id, 24) }}</code>
        </p>
    </div>
    <div class="d-flex gap-2">
        @if($conversation->needs_human)
        <form method="POST" action="{{ route('chatbot.admin.resolve', $conversation) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-success btn-action">
                <i class="bi bi-check-lg"></i> Marcar resuelta
            </button>
        </form>
        @endif
        <a href="{{ route('chatbot.admin.conversations') }}" class="btn btn-outline-secondary btn-action">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body chatbot-admin-thread" style="max-height: 60vh; overflow-y: auto;">
        @foreach($history as $msg)
        <div class="d-flex mb-3 {{ $msg->role === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
            <div class="p-3 rounded-3 {{ $msg->role === 'user' ? 'bg-primary text-white' : 'bg-light border' }}" style="max-width: 75%;">
                <div class="small fw-semibold mb-1">{{ $msg->role === 'user' ? 'Usuario' : 'Asistente' }}</div>
                <div>{{ $msg->user_message }}</div>
                @if($msg->ai_response)
                <hr class="my-2 opacity-25">
                <div class="small">{{ $msg->ai_response }}</div>
                @endif
                <div class="mt-2 small opacity-75">{{ $msg->created_at->format('d/m/Y H:i:s') }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@if($conversation->needs_human)
<div class="alert alert-warning mt-3 d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>Esta conversación fue escalada y requiere atención humana.</div>
</div>
@endif
@endsection
