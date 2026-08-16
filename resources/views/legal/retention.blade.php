@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-2">Política de Retención y Borrado de Datos</h1>
                <p class="text-muted mb-4">Última actualización: 26 de julio de 2026 · Versión 1.0</p>

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Importante:</strong> Los datos financieros y de auditoría <strong>no se eliminan automáticamente</strong>.
                    Esta política describe los tiempos de conservación; el borrado requiere acción manual del administrador.
                </div>

                <h2 class="h5 mt-4">1. Tiempos de conservación por tipo de dato</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Tipo de dato</th>
                                <th>Conservación mínima</th>
                                <th>¿Se borra automáticamente?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Facturas e ítems</td>
                                <td>Indefinida (mientras exista el condominio)</td>
                                <td>No</td>
                            </tr>
                            <tr>
                                <td>Reportes de pago y comprobantes</td>
                                <td>Indefinida (borrado lógico; adjuntos físicos se conservan)</td>
                                <td>No</td>
                            </tr>
                            <tr>
                                <td>Registros de auditoría (AuditLog)</td>
                                <td>Indefinida</td>
                                <td>No</td>
                            </tr>
                            <tr>
                                <td>Movimientos de cuentas y fondos</td>
                                <td>Indefinida</td>
                                <td>No</td>
                            </tr>
                            <tr>
                                <td>Conversaciones del chatbot</td>
                                <td>Indefinida (para trazabilidad)</td>
                                <td>No</td>
                            </tr>
                            <tr>
                                <td>Logs de aplicación (Laravel)</td>
                                <td>Rotación diaria (configurable)</td>
                                <td>Sí (rotación automática de archivos)</td>
                            </tr>
                            <tr>
                                <td>Tokens de sesión (Sanctum)</td>
                                <td>30 días (configurable)</td>
                                <td>Sí (expiración automática)</td>
                            </tr>
                            <tr>
                                <td>Códigos 2FA</td>
                                <td>10 minutos</td>
                                <td>Sí (expiración automática)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h2 class="h5 mt-4">2. Borrado manual</h2>
                <p>
                    El borrado de datos financieros o de auditoría requiere intervención manual del
                    <strong>super_admin</strong> y debe realizarse conforme a la normativa fiscal y
                    de protección de datos aplicable. El sistema no ofrece borrado automático de
                    estos registros.
                </p>

                <h2 class="h5 mt-4">3. Derechos del titular</h2>
                <p>
                    El titular puede solicitar acceso, rectificación o supresión de sus datos personales
                    conforme a la <a href="{{ route('legal.privacy') }}">Política de Privacidad</a>.
                    La supresión de datos financieros está sujeta a las obligaciones legales de conservación.
                </p>

                <h2 class="h5 mt-4">4. Backups</h2>
                <p class="mb-0">
                    Los backups diarios conservan copias de las bases de datos. La retención de backups
                    se configura a nivel de infraestructura (recomendado: 7 días diarios + 4 semanales).
                </p>
            </div>
        </div>
    </div>
</div>
@endsection