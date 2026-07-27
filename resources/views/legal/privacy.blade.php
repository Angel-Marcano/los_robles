@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-2">Política de Privacidad</h1>
                <p class="text-muted mb-4">Última actualización: 26 de julio de 2026 · Versión 1.0</p>

                <h2 class="h5 mt-4">1. Datos que recolectamos</h2>
                <ul>
                    <li><strong>Identificación:</strong> nombre, apellido, tipo y número de documento, correo electrónico.</li>
                    <li><strong>Patrimoniales:</strong> propiedad de apartamentos, facturas, pagos, saldos, morosidad.</li>
                    <li><strong>Acceso y seguridad:</strong> direcciones IP, logs de auditoría, tokens de sesión, actividad 2FA.</li>
                    <li><strong>Comprobantes:</strong> archivos adjuntos de pagos (imágenes/PDF) subidos por el usuario.</li>
                </ul>

                <h2 class="h5 mt-4">2. Finalidad del tratamiento</h2>
                <ul>
                    <li>Gestión administrativa y financiera del condominio (facturación, cobranza, reportes).</li>
                    <li>Verificación de identidad y seguridad de la cuenta (2FA, auditoría).</li>
                    <li>Comunicaciones relacionadas con el servicio (correos de factura, recordatorios, soporte).</li>
                    <li>Cumplimiento de obligaciones legales y regulatorias.</li>
                </ul>

                <h2 class="h5 mt-4">3. Base legal</h2>
                <p>
                    El tratamiento se realiza con base en la ejecución de un contrato (prestación del servicio
                    de administración condominal), el cumplimiento de obligaciones legales y el consentimiento
                    explícito del titular para datos opcionales (como notificaciones por SMS).
                </p>

                <h2 class="h5 mt-4">4. Retención de datos</h2>
                <p>
                    Los datos se conservan mientras exista la relación contractual con el condominio y,
                    posteriormente, durante los plazos legales aplicables. Las facturas, comprobantes de pago
                    y registros de auditoría <strong>no se eliminan automáticamente</strong>; consulte la
                    <a href="{{ route('legal.retention') }}">Política de Retención</a> para el detalle.
                </p>

                <h2 class="h5 mt-4">5. Terceros y proveedores</h2>
                <ul>
                    <li><strong>Correo electrónico:</strong> envío de notificaciones (facturas, 2FA, soporte).</li>
                    <li><strong>SMS (EnviaTuSMS):</strong> notificaciones transaccionales, con consentimiento previo.</li>
                    <li><strong>Almacenamiento de adjuntos:</strong> disco local o servicio en la nube (S3).</li>
                    <li><strong>IA (DeepSeek):</strong> asistente conversacional, con sanitización de datos sensibles.</li>
                </ul>
                <p>Los proveedores actúan como encargados del tratamiento y están obligados a confidencialidad.</p>

                <h2 class="h5 mt-4">6. Derechos del titular (ARCO)</h2>
                <p>
                    Puede ejercer sus derechos de acceso, rectificación, cancelación, oposición y portabilidad
                    contactando al administrador de su condominio o al canal de soporte oficial de la plataforma.
                </p>

                <h2 class="h5 mt-4">7. Contacto</h2>
                <p class="mb-0">
                    Para consultas sobre privacidad, contacte al administrador de su condominio
                    o al canal de soporte oficial de la plataforma.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection