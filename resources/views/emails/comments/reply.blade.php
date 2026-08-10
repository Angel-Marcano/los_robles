@component('mail::message')
# Nuevo comentario

Hola,

Tienes un nuevo comentario en **{{ $entityLabel }}** de parte de **{{ $comment->user->name ?? 'Administración' }}**:

> {{ $comment->message }}

@component('mail::button', ['url' => $url])
Ver conversación
@endcomponent

Gracias,
{{ app()->bound('currentCondominium') ? app('currentCondominium')->name : config('app.name', 'Los Robles') }}
@endcomponent
