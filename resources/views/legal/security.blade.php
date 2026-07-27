@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-2">Seguridad de la Plataforma</h1>
                <p class="text-muted mb-4">Última actualización: 26 de julio de 2026 · Versión 1.0</p>

                <h2 class="h5 mt-4">1. Prácticas de seguridad implementadas</h2>
                <ul>
                    <li><strong>Verificación en dos pasos (2FA):</strong> opcional por usuario, por correo o app autenticadora (TOTP).</li>
                    <li><strong>Cifrado en tránsito:</strong> HTTPS/TLS para toda la comunicación.</li>
                    <li><strong>Cifrado de secretos:</strong> claves 2FA y tokens almacenados cifrados en la base de datos.</li>
                    <li><strong>Firma criptográfica de facturas:</strong> HMAC con invalidación automática ante cambios críticos.</li>
                    <li><strong>Auditoría:</strong> registro de acciones sensibles (creación, edición, anulación, pagos, reservas).</li>
                    <li><strong>Rate limiting:</strong> límites por IP y por usuario en login, API y chatbot.</li>
                    <li><strong>Sanitización de PII:</strong> datos sensibles se reemplazan antes de enviar a servicios de IA.</li>
                    <li><strong>Borrado lógico:</strong> comprobantes y reportes de pago se marcan, no se eliminan físicamente.</li>
                </ul>

                <h2 class="h5 mt-4">2. Buenas prácticas para usuarios</h2>
                <ul>
                    <li>Active la verificación en dos pasos desde "Mi Perfil".</li>
                    <li>Use contraseñas robustas (mínimo 8 caracteres, combinando letras, números y símbolos).</li>
                    <li>No comparta sus credenciales; notifique accesos no autorizados de inmediato.</li>
                    <li>Cierre sesión al terminar en equipos compartidos.</li>
                    <li>Verifique la URL antes de ingresar credenciales (evite phishing).</li>
                </ul>

                <h2 class="h5 mt-4">3. Backups</h2>
                <p>
                    Se realizan copias de seguridad automáticas diarias de las bases de datos (master y tenant)
                    con retención configurable. Los backups se almacenan de forma segura.
                </p>

                <h2 class="h5 mt-4">4. Reporte responsable de fallos</h2>
                <p>
                    Si detecta una vulnerabilidad o comportamiento inesperado, repórtelo de forma responsable
                    al administrador de su condominio o al canal de soporte oficial. No divulgue públicamente
                    fallos de seguridad hasta que hayan sido resueltos.
                </p>

                <h2 class="h5 mt-4">5. Contacto</h2>
                <p class="mb-0">
                    Para consultas de seguridad, contacte al administrador de su condominio
                    o al canal de soporte oficial de la plataforma.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection