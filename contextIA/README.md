# ContextIA — Contexto para conversaciones con IA

Esta carpeta contiene archivos `.md` con el contexto detallado de cada módulo / funcionalidad
del sistema **Los Robles** (administración de condominios, Laravel multi-tenant).

## Cómo usarlo

Cada archivo describe un módulo: modelos, controladores, servicios, reglas de negocio,
rutas, policies, observadores y casos de uso. Carga el archivo relevante como contexto
en futuras conversaciones con la IA antes de pedir cambios sobre ese módulo.

## Índice de módulos

| Archivo | Módulo |
|--------|--------|
| `condominio.md` | Condominios, multi-tenancy, torres y apartamentos |
| `cliente.md` | Usuarios, propietarios, ownership, roles y permisos |
| `factura.md` | Facturación: facturas padre/hija, ítems, mora, aprobación, anulación, reemisión |
| `cobro.md` | Reportes de pago, aprobación/rechazo, abonos, comprobantes |
| `moneda.md` | Tasa de cambio (USD/VES) y conversión de divisas |
| `cuentas.md` | Cuentas internas, movimientos, transferencias, cambio de divisas |
| `fondo_reserva.md` | Fondo de reserva por torre, movimientos, acreditación desde facturas |
| `gastos.md` | Ítems de gasto (ExpenseItem) — catálogo de conceptos facturables |
| `verificacion_factura.md` | Firma HMAC, token de verificación, QR y página pública de validez |
| `reportes.md` | Reportes de morosidad mensual y exportación |
| `auditoria.md` | AuditLog, AuditService y trazabilidad |
| `soporte.md` | Tickets de soporte (SupportTicket) |
| `auth_seguridad.md` | Login, 2FA (email/TOTP), recuperación de contraseña, perfil |
| `chatbot.md` | Asistente IA (DeepSeek), orquestador, tools, guardrails, handoff |
| `integraciones.md` | Servicios externos: EnviaTuSMS, DeepSeek, correo, almacenamiento |

## Convenciones del proyecto

- **Stack**: Laravel (PHP 7.4 compatible) + Blade + Bootstrap 5 + Dompdf + Spatie Permission.
- **Multi-tenant**: cada condominio tiene su propia BD (conexión `tenant`); el trait
  `App\Models\Traits\UsesTenantConnection` resuelve la conexión según `currentCondominium`.
- **Roles**: `super_admin`, `condo_admin`, `tower_admin`, `owner`, `tenant`.
- **Moneda dual**: todos los montos se manejan en USD y VES; la tasa activa viene de
  `CurrencyRate` (una sola activa a la vez).
- **Idioma**: español; respuestas y vistas en español.