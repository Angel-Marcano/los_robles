@extends('layouts.app')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-chat-dots me-2"></i>Conversaciones del chatbot</h1>
        <p class="text-muted mb-0">Supervisión y escalamiento a humanos.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('chatbot.admin.conversations') }}" class="btn btn-outline-secondary btn-sm {{ request('needs_human') ? '' : 'active' }}">Todas</a>
        <a href="{{ route('chatbot.admin.conversations', ['needs_human' => 1]) }}" class="btn btn-outline-warning btn-sm {{ request('needs_human') ? 'active' : '' }}">Requieren humano</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Sesión</th>
                    <th>Último mensaje</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conv)
                <tr>
                    <td>#{{ $conv->id }}</td>
                    <td>{{ $conv->user?->name ?? 'Anónimo' }}<br><span class="text-muted small">{{ $conv->user?->email }}</span></td>
                    <td><code>{{ Str::limit($conv->session_id, 20) }}</code></td>
                    <td>{{ Str::limit($conv->user_message, 60) }}</td>
                    <td>
                        @if($conv->needs_human)
                            <span class="badge bg-warning text-dark">Requiere humano</span>
                        @else
                            <span class="badge bg-success">Resuelto</span>
                        @endif
                    </td>
                    <td>{{ $conv->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('chatbot.admin.conversation', $conv) }}" class="btn btn-sm btn-primary btn-action">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                        @if($conv->needs_human)
                        <form method="POST" action="{{ route('chatbot.admin.resolve', $conv) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-success btn-action">
                                <i class="bi bi-check-lg"></i> Resolver
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p class="mb-0">No hay conversaciones registradas.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($conversations->hasPages())
    <div class="card-footer d-flex justify-content-center">
        {{ $conversations->links() }}
    </div>
    @endif
</div>
@endsection
