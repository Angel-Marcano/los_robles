@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-2">Terminos y Condiciones de Uso</h1>
                <p class="text-muted mb-4">Ultima actualizacion: {{ now()->format('Y-m-d') }}</p>

                <h2 class="h5 mt-4">1. Alcance del servicio</h2>
                <p>
                    Los Robles es una plataforma para la gestion administrativa y financiera de condominios.
                    El uso del sistema implica la aceptacion de estos terminos por parte de administradores,
                    propietarios, residentes y usuarios autorizados.
                </p>

                <h2 class="h5 mt-4">2. Responsabilidades del usuario</h2>
                <ul>
                    <li>Mantener la confidencialidad de sus credenciales de acceso.</li>
                    <li>Proveer informacion veraz y actualizada para la gestion de su cuenta.</li>
                    <li>Usar la plataforma conforme a la ley y a las normas internas del condominio.</li>
                    <li>Notificar de inmediato accesos no autorizados o incidentes de seguridad.</li>
                </ul>

                <h2 class="h5 mt-4">3. Limites del servicio</h2>
                <ul>
                    <li>El sistema puede tener ventanas de mantenimiento o interrupciones no previstas.</li>
                    <li>La plataforma no sustituye asesorias legales, contables o tributarias especializadas.</li>
                    <li>Las decisiones de cobro, sancion o gestion interna corresponden al condominio.</li>
                </ul>

                <h2 class="h5 mt-4">4. Uso aceptable</h2>
                <p>Queda prohibido:</p>
                <ul>
                    <li>Manipular datos de terceros sin autorizacion.</li>
                    <li>Intentar vulnerar la seguridad, integridad o disponibilidad del sistema.</li>
                    <li>Usar la plataforma para fines fraudulentos, abusivos o contrarios a la ley.</li>
                </ul>

                <h2 class="h5 mt-4">5. Vigencia y cambios</h2>
                <p>
                    Estos terminos estan vigentes desde su publicacion. Los Robles puede actualizarlos
                    cuando sea necesario por cambios legales, operativos o de seguridad. La version vigente
                    sera la publicada en esta pagina.
                </p>

                <h2 class="h5 mt-4">6. Contacto</h2>
                <p class="mb-0">
                    Para consultas sobre estos terminos, contacte al administrador de su condominio
                    o al canal de soporte oficial de la plataforma.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
