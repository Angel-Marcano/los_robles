@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-2">Política de Cookies</h1>
                <p class="text-muted mb-4">Última actualización: 26 de julio de 2026 · Versión 1.0</p>

                <h2 class="h5 mt-4">1. ¿Qué son las cookies?</h2>
                <p>
                    Las cookies son pequeños archivos de texto que el sitio web almacena en su navegador
                    para recordar preferencias y mejorar la experiencia de uso.
                </p>

                <h2 class="h5 mt-4">2. Tipos de cookies que utilizamos</h2>

                <h3 class="h6 mt-3">Cookies técnicas (necesarias)</h3>
                <p>Imprescindibles para el funcionamiento del sistema. No requieren consentimiento.</p>
                <ul>
                    <li><strong>sesión (XSRF-TOKEN, laravel_session):</strong> autenticación, protección CSRF y mantenimiento de la sesión.</li>
                    <li><strong>lr-theme:</strong> recuerda su preferencia de tema (claro/oscuro).</li>
                </ul>

                <h3 class="h6 mt-3">Cookies analíticas (opcionales)</h3>
                <p>
                    Permiten medir el uso del sitio de forma agregada y anónima. <strong>Requieren su consentimiento.</strong>
                    Si no las acepta, el sistema seguirá funcionando con normalidad.
                </p>
                <ul>
                    <li><strong>lr-analytics:</strong> indicador de consentimiento de analíticas (1 = aceptado, 0 = rechazado).</li>
                </ul>

                <h2 class="h5 mt-4">3. Finalidad</h2>
                <ul>
                    <li><strong>Técnicas:</strong> permitir login, recordar sesión y preferencias visuales.</li>
                    <li><strong>Analíticas:</strong> entender patrones de uso para mejorar la plataforma (solo si acepta).</li>
                </ul>

                <h2 class="h5 mt-4">4. Gestión del consentimiento</h2>
                <p>
                    Al ingresar al sistema verá un banner de cookies. Puede aceptar todas, aceptar solo las técnicas
                    (rechazar analíticas) o cambiar su preferencia en cualquier momento desde el enlace
                    <a href="{{ route('legal.cookies') }}">Política de Cookies</a> en el pie de página.
                </p>

                <h2 class="h5 mt-4">5. Duración</h2>
                <ul>
                    <li>Cookies de sesión: se eliminan al cerrar el navegador.</li>
                    <li><strong>lr-theme</strong> y <strong>lr-analytics</strong>: persistentes (1 año).</li>
                </ul>

                <h2 class="h5 mt-4">6. Desactivación</h2>
                <p>
                    Puede bloquear o eliminar cookies desde la configuración de su navegador. Las cookies técnicas
                    son necesarias; si las bloquea, el sistema no funcionará correctamente.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection