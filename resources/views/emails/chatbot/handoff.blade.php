@component('mail::message')
# Escalamiento a humano desde el chatbot

El usuario **{{ $user->name }}** ({{ $user->email }}) ha solicitado atención humana o el chatbot no pudo resolver su consulta.

**Sesión:** {{ $conversation->session_id }}  
**Canal:** {{ $conversation->channel }}  
**Intención detectada:** {{ $conversation->intent ?? 'N/D' }}  
**Fecha:** {{ $conversation->created_at->format('d/m/Y H:i') }}

@component('mail::button', ['url' => route('chatbot.admin.conversation', $conversation)])
Ver conversación
@endcomponent

Gracias,  
{{ config('app.name') }}
@endcomponent
