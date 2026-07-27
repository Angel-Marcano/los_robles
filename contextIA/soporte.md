# Módulo: Soporte (Tickets)

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Sistema básico de **tickets de soporte** para que propietarios/residentes reporten
incidencias (facturación, mantenimiento, seguridad, general). También es creado por el
chatbot cuando un usuario solicita un ticket.

## Modelo

### `App\Models\SupportTicket` (BD tenant)
- **Fillable**: `user_id`, `condominium_id`, `category`, `priority`, `description`,
  `status`, `assigned_to`, `resolved_at`.
- **Casts**: `resolved_at` datetime.
- **Relaciones**: `user()` → belongsTo `User`; `assignee()` → belongsTo `User` (`assigned_to`).
- Usa `UsesTenantConnection`.
- **Categorías**: `billing`, `maintenance`, `security`, `general`.
- **Prioridades**: `low`, `medium`, `high`.
- **Status**: `open`, (otros estados según flujo — ver implementación).

## Creación desde el chatbot

`App\Services\Chatbot\Tools\CreateSupportTicketTool`:
- `execute(User, $args, $context)`:
  - Requiere `description` (si falta, pide info).
  - Normaliza `category` (mapea `facturacion`→`billing`, `mantenimiento`→`maintenance`,
    `seguridad`→`security`, `soporte`/`general`→`general`).
  - Normaliza `priority` (`baja`→`low`, `media`→`medium`, `alta`→`high`).
  - Pide **confirmación explícita** antes de crear.
- `confirm(User, $data)`:
  - Crea `SupportTicket` con `status='open'`, `user_id`, `condominium_id` del contexto.
  - Retorna mensaje con el número de ticket.

## Reglas de negocio clave

1. **Confirmación obligatoria** cuando se crea desde el chatbot.
2. **Categoría y prioridad** se normalizan desde español/inglés.
3. `condominium_id` se toma del contexto del chatbot (`currentCondominium`).
4. No hay controlador web dedicado para gestión manual de tickets (solo creación vía
   chatbot). Pendiente implementar panel de administración.

## Casos de uso típicos
- Propietario pide al chatbot: "tengo una fuga en mi apartamento" → ticket maintenance.
- Chatbot crea ticket y da el número al usuario.
- Admin asigna ticket (`assigned_to`) y resuelve (`resolved_at`).

## Notas / deudas
- No hay controlador/vista para listar y gestionar tickets (solo modelo + tool del chatbot).
- No hay policy definida para `SupportTicket`.
- El flujo de asignación/resolución no está implementado en UI.