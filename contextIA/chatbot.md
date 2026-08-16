# Módulo: Chatbot IA (DeepSeek)

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.
> Plan completo en `plan_chatbot.md`. Base de conocimiento en `resources/chatbot/kb/`.

## Resumen

Asistente conversacional **"RobleBot"** embebido en el dashboard. Usa **DeepSeek**
(`deepseek-chat`) vía SDK OpenAI-compatible. Clasifica intención (reglas + LLM), ejecuta
**tools** para leer/escribir datos, pide confirmación para acciones sensibles, aplica
guardrails de dominio y escala a humano cuando es necesario.

> **Estado actual**: el widget del chat está **oculto temporalmente** en
> `resources/views/layouts/app.blade.php` (comentado con `@php ... @endphp`) porque el
> diseño no se ve bien. La lógica backend sigue activa; solo se quitó el `@include('chatbot.widget')`.

## Arquitectura

```
Usuario (web) → ChatbotController → ChatOrchestrator
  → Guardrails (dominio)
  → HumanHandoffService (escalamiento)
  → ConfirmationManager (acción pendiente)
  → HistoryLoader (contexto)
  → IntentClassifier (reglas + DeepSeek)
  → Tools (lectura/escritura)
  → DeepSeekClient (respuesta final)
  → ChatbotConversation (persistencia)
```

## Cliente LLM — `App\Services\Chatbot\DeepSeekClient`
- Usa `OpenAI::factory()` con `base_url=https://api.deepseek.com` y `DEEPSEEK_API_KEY`.
- `chat(array $messages, ?array $tools, ?string $model)`:
  - Modelo default `deepseek-chat`; `temperature` 0.3; `max_tokens` 1024.
  - Soporta function calling (`tool_choice='auto'`).
  - Retorna `['content', 'tool_calls', 'usage', 'model']`.
- Config en `config/services.php` → `services.deepseek.*`.

## Orquestador — `App\Services\Chatbot\ChatOrchestrator`
Flujo `handle(User, $message, $sessionId, $channel='web')`:
1. **Contexto** (`ContextBuilder`) — apartamentos del usuario, `is_admin`, `default_apartment_id`.
2. **Sanitización** (`PiiSanitizer`) — reemplaza email/teléfono/cédula por tokens.
3. **Guardrails** (`Guardrails::validate`) — dominio y límites; si bloquea, guarda y retorna.
4. **Handoff** (`HumanHandoffService::isHandoffRequest`) — si pide humano, escala y retorna.
5. **Acción pendiente** (`ConfirmationManager::getPendingAction`) — si hay, maneja confirmación.
6. **Historial** (`HistoryLoader::load`) — últimos 10 mensajes (formato OpenAI).
7. **Clasificación** (`IntentClassifier::classify`) — reglas rápidas + DeepSeek fallback.
8. **Ejecución** según intención → tool correspondiente.
9. Si la tool pide **confirmación** → guarda acción pendiente (5 min) y retorna prompt.
10. Si la tool pide **más info** → retorna mensaje.
11. **Respuesta final** con DeepSeek (`generateFinalResponse`).
12. **Persistencia** (`saveConversation`) — guarda `ChatbotConversation`.

### Intenciones (`ChatbotConfig::allowedIntents`)
`faq`, `balance`, `due_date`, `payment_status`, `report_payment`, `create_ticket`,
`human_handoff`, `unknown`.

## Componentes

### `ChatbotConfig`
- `systemPrompt()` — identidad "RobleBot", reglas (dominio condominio, no inventar datos,
  no revelar otros propietarios, confirmar acciones, español, no SQL/código).
- `intentClassificationPrompt()` — clasifica en JSON `{intent, confidence, entities}`.
- `knowledgeBasePath()` → `resource_path('chatbot/kb')`.
- `maxHistoryMessages()` (default 20), `rateLimitPerMinute()` (default 10).

### `ContextBuilder`
- Construye contexto seguro: `user_id`, `name`, `is_admin`, `apartments` (id, code, tower),
  `default_apartment_id`. **Nunca** incluye datos de otros usuarios.

### `IntentClassifier`
- `classify($message, $history)`:
  - Primero **reglas rápidas** (`ruleBasedClassify`) — match por palabras clave.
  - Si no hay match, llama a DeepSeek con `intentClassificationPrompt` + historial.
  - Valida que el intent esté en `allowedIntents`; si no, `unknown`.
  - `extractEntities` — apartment_id, invoice_id, amount_usd/ves, category, description.

### `Guardrails`
- `domainKeywords()` — palabras del dominio (factura, pago, apartamento, reglas, ticket...).
- `blockedPatterns()` — jailbreak/prompt injection ("ignora instrucciones", "modo dios"...).
- `validate($message, $sessionId, $userId)` — retorna `['allowed' => bool, 'reason' => string]`.

### `PiiSanitizer`
- `sanitize($text)` — reemplaza email, teléfono (04xx, +58), cédula (V-/E-) por tokens.
- `sanitizeOutput($text)` — igual (limpieza de output).

### `HistoryLoader`
- `load(User, $sessionId, $limit)` — últimos N mensajes (max 50) en formato OpenAI.
- Excluye `blocked_by_guardrails` y mensajes sin input/output.

### `ConfirmationManager`
- `storePendingAction(User, $sessionId, $actionData)` — crea `ChatbotConversation` con
  `is_action_pending=true`, expira en 5 min.
- `getPendingAction` — vigente (no expirado).
- `resolvePendingAction` — marca resuelto.
- `parseConfirmation($message)` — `confirmed`/`rejected`/neutro.

### `HumanHandoffService`
- `isHandoffRequest($message)` — triggers: "administrador", "humano", "hablar con",
  "reclamo", "queja formal", "abogado", "no me ayudas"...
- `escalate(ChatbotConversation, User)`:
  - Marca `needs_human=true`.
  - Notifica a admins (`super_admin`, `condo_admin`) con `ChatbotHandoffMail` (cola).

## Tools (`App\Services\Chatbot\Tools`)

Todas implementan `ToolInterface` (`name`, `description`, `parameters`, `execute`).
Retornan `ToolResult` (`ok`, `error`, `needsConfirmation`, `needsInfo`).

| Tool | Intent | Acción |
|------|--------|--------|
| `GetInvoiceBalanceTool` | `balance` | Saldo, mora y última factura del apartamento. |
| `GetDueDateTool` | `due_date` | Fecha de vencimiento de la última factura. |
| `GetPaymentStatusTool` | `payment_status` | Últimos reportes de pago (status, montos). |
| `ReportPaymentTool` | `report_payment` | Crea `PaymentReport` (requiere confirmación). |
| `CreateSupportTicketTool` | `create_ticket` | Crea `SupportTicket` (requiere confirmación). |
| `GetCondoRulesTool` | `faq` | Lee `resources/chatbot/kb/{topic}.md` (max 4000 chars). |

### Validaciones de tools
- Verifican **ownership** del apartamento (o `is_admin`).
- `ReportPaymentTool`: valida factura no anulada, pide datos faltantes (monto, método,
  referencia, fecha), captura tasa activa, pide confirmación.
- `CreateSupportTicketTool`: normaliza categoría/prioridad, pide confirmación.

## Persistencia — `App\Models\ChatbotConversation`
- **Fillable**: `user_id`, `condominium_id`, `channel`, `session_id`, `intent`,
  `prompt_version`, `input_raw`, `input_sanitized`, `output_raw`, `output_sanitized`,
  `tools_called` (array), `actions_executed` (array), `context` (array), `tokens_input`,
  `tokens_output`, `model`, `duration_ms`, `needs_human`, `is_action_pending`,
  `pending_action` (array), `pending_action_expires_at`.
- Scopes: `forUser`, `forSession`.

## Controladores

### `ChatbotController` (endpoint del chat)
- `message(Request)`:
  - Requiere auth. Valida `message` (max 1000), `session_id` (nullable max 64).
  - **Rate limit**: 10/min por usuario (`chatbot:{user_id}`).
  - Genera `session_id` UUID si no viene.
  - Llama `ChatOrchestrator::handle()`.
- `history(Request)` — últimas 50 conversaciones del usuario (opcional por sesión).

### `ChatbotAdminController` (panel admin)
- `conversations(Request)` — lista con `user`, filtros `needs_human`, `session_id`.
- `conversation(ChatbotConversation)` — historial de la sesión.
- `resolve(ChatbotConversation)` — marca `needs_human=false`.

## Policy: `ChatbotConversationPolicy`
- `viewAny`, `view`, `update` → `User::isAdmin()`.
- `create`, `delete` → false.

## Rutas (`routes/web.php`)
- `chatbot.message` (POST, auth) — `chatbot/message`.
- `chatbot.history` (GET, auth) — `chatbot/history`.
- `chatbot.admin.conversations`, `chatbot.admin.conversation`, `chatbot.admin.resolve`.

## Vistas
- `resources/views/chatbot/widget.blade.php` — widget flotante (toggle, panel, mensajes,
  typing, form). **Actualmente oculto** en el layout.
- `resources/views/chatbot/admin/` — conversations, conversation.
- `resources/views/emails/chatbot/handoff.blade.php` — correo de escalamiento.

## Base de conocimiento
- `resources/chatbot/kb/` — archivos `.md` por tema (`general.md`, `billing.md`,
  `payments.md`, `support.md`). `GetCondoRulesTool` los lee (max 4000 chars).

## Reglas de negocio clave

1. **Dominio acotado**: solo temas del condominio; fuera de dominio se bloquea.
2. **No inventar datos**: usa tools para consultar; el LLM solo redacta la respuesta.
3. **Privacidad**: contexto solo del usuario actual; PII sanitizada antes del LLM.
4. **Confirmación obligatoria** para `report_payment` y `create_support_ticket`.
5. **Auditable**: toda conversación se guarda con intent, tools, duración, tokens.
6. **Rate limit** 10 msg/min por usuario.
7. **Escalamiento humano** detecta reclamos graves o peticiones explícitas.

## Casos de uso típicos
- "¿Cuánto debo?" → `balance` → `GetInvoiceBalanceTool`.
- "¿Cuándo vence mi factura?" → `due_date`.
- "Reporté un pago, ¿está aprobado?" → `payment_status`.
- "Quiero reportar un pago de 100 USD por Zelle ref 123" → `report_payment` → confirmación.
- "Tengo una fuga" → `create_ticket` → confirmación.
- "Quiero hablar con un administrador" → `human_handoff`.

## Notas / deudas
- **Widget oculto** por diseño feo (2026-07-25). Reactivar mejorando UI en `widget.blade.php`.
- `generateFinalResponse` y `saveConversation` — ver implementación completa en
  `ChatOrchestrator.php` (no se leyó todo en este contexto).
- Futuro: canal WhatsApp/SMS con el mismo orquestador (distinto adapter de entrada).