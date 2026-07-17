# Plan de Implementación — Chatbot Interno Los Robles

> Fecha: 2026-07-17  
> Modelo LLM: DeepSeek (`deepseek-chat` por defecto, `deepseek-reasoner` opcional para razonamiento complejo)  
> Canal inicial: web embebido en el dashboard de Los Robles  
> Alcance MVP: lectura de datos + acciones controladas con confirmación inline + guardrails de dominio

---

## 1. Visión y objetivos

Construir un asistente conversacional interno que permita a propietarios, administradores y personal de torre resolver preguntas frecuentes y ejecutar acciones simples sin salir del sistema. El chatbot debe ser:

- **Seguro**: nunca expone datos de otros usuarios ni ejecuta acciones sin permisos.
- **Trazable**: toda conversación y acción queda auditada.
- **Extensible**: la arquitectura debe permitir agregar WhatsApp/SMS en fases posteriores sin reescribir la lógica de negocio.
- **Económico**: usar `deepseek-chat` para la mayoría de las interacciones y reservar `deepseek-reasoner` solo para casos complejos.

---

## 2. Decisiones de diseño tomadas

| Tema | Decisión |
|------|----------|
| Modelo principal | `deepseek-chat` (compatible OpenAI, bajo costo, buen rendimiento para FAQ y clasificación) |
| Modelo secundario | `deepseek-reasoner` reservado para análisis complejo (morosidad, resúmenes) |
| Cliente API | SDK oficial de OpenAI apuntando a `baseURL: https://api.deepseek.com` |
| Autenticación API | Variable de entorno `DEEPSEEK_API_KEY` validada al arrancar |
| Canales fase 1 | Widget web embebido en el dashboard |
| Canales futuros | WhatsApp webhook (misma capa de orquestación, distinto adapter de entrada) |
| Base de conocimiento | Archivos markdown versionados en `resources/chatbot/kb/` + caché en BD |
| Acciones sensibles | Confirmación inline en el chat + validación de permisos + auditoría |
| Persistencia | Tabla `chatbot_conversations` en BD tenant |

---

## 3. Arquitectura general

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              USUARIO                                         │
│  (dashboard web / futuro WhatsApp / futuro SMS)                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ChatController / WebhookController                   │
│  - Recibe mensaje                                                            │
│  - Identifica usuario y condominio (tenant)                                  │
│  - Aplica rate limiting por usuario                                            │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ChatOrchestratorService                            │
│  1. Carga contexto seguro (apartamentos del usuario, permisos)               │
│  2. Construye historial de conversación                                       │
│  3. Clasifica intención (reglas + LLM)                                        │
│  4. Si es lectura: ejecuta Tool de consulta                                   │
│  5. Si es escritura: pide datos faltantes y confirma explícitamente           │
│  6. Genera respuesta final con DeepSeek                                       │
│  7. Guarda auditoría                                                          │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         DeepSeekClient                                       │
│  - Wrapper sobre SDK OpenAI con baseURL de DeepSeek                           │
│  - Manejo de timeouts, reintentos, errores de API                             │
│  - Logging estructurado sin PII                                               │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         Tools de negocio                                   │
│  getInvoiceBalanceTool, getDueDateTool, reportPaymentTool,                   │
│  createSupportTicketTool, getPaymentStatusTool, getCondoRulesTool            │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Fases de implementación

### Fase 0 — Preparación y configuración (1 día)

**Objetivo**: tener el proyecto listo para consumir DeepSeek de forma segura.

- [x] Crear cuenta en DeepSeek Platform y generar API key de prueba.
- [x] Agregar variables de entorno:
  ```env
  DEEPSEEK_API_KEY=sk-...
  DEEPSEEK_BASE_URL=https://api.deepseek.com
  DEEPSEEK_MODEL=deepseek-chat
  DEEPSEEK_REASONER_MODEL=deepseek-reasoner
  DEEPSEEK_TIMEOUT=30
  DEEPSEEK_MAX_TOKENS=1024
  DEEPSEEK_TEMPERATURE=0.3
  CHATBOT_RATE_LIMIT_PER_MINUTE=10
  CHATBOT_MAX_HISTORY_MESSAGES=20
  CHATBOT_MAX_OPERATIONS_PER_SESSION=30
  CHATBOT_MAX_OPERATIONS_PER_USER_HOUR=60
  ```
- [x] Instalar dependencia:
  ```bash
  composer require openai-php/client
  ```
- [x] Crear `config/services.php` → agregar sección `deepseek`.
- [x] Crear `App\Services\Chatbot\DeepSeekClient`:
  - constructor recibe config.
  - método `chat(array $messages, ?array $tools = null, ?string $model = null): array`.
  - manejo de `Timeout`, `RateLimit`, `Authentication` errors.
  - logging estructurado: `model`, `tokens_input`, `tokens_output`, `duration_ms`, sin PII.
- [x] Crear `App\Services\Chatbot\ChatbotConfig` para centralizar prompts, tools y límites.
- [x] Smoke test: llamada simple a DeepSeek desde tinker/artisan command.
- [x] Fix SSL: configurar `curl.cainfo` en `php.ini` apuntando a `C:\laragon\etc\ssl\cacert.pem`.

**Entregable**: comando `php artisan chatbot:ping` que responda “Pong desde DeepSeek”. ✅

---

### Fase 1 — Motor de conversación + FAQ (3–4 días)

**Objetivo**: chatbot web que responda preguntas frecuentes y consulte datos del usuario autenticado.

- [x] Crear migraciones (landlord + tenant):
  - `chatbot_conversations`:
    - `id`, `user_id`, `condominium_id`, `channel` (`web`/`whatsapp`),
    - `session_id`, `intent`, `prompt_version`,
    - `input_raw` (texto original), `input_sanitized`,
    - `output_raw`, `output_sanitized`,
    - `tools_called` (JSON), `actions_executed` (JSON),
    - `tokens_input`, `tokens_output`, `model`, `duration_ms`,
    - `needs_human`, `is_action_pending`, `pending_action`, `pending_action_expires_at`,
    - `created_at`, `updated_at`.
  - `support_tickets` (creada para acciones de soporte).
  - Campos extendidos en `payment_reports`: `apartment_id`, `reported_by`, `payment_method`, `reference_number`, `paid_at`, `usd_equivalent`.
  - Campos extendidos en `invoices`: `number`, `parent_id`, `apartment_id`, `paid_exchange_rate`, `owner_name`, `owner_email`, `owner_document`.
- [x] Crear modelo `App\Models\ChatbotConversation` con `UsesTenantConnection`.
- [x] Crear `App\Services\Chatbot\ChatOrchestrator`:
  - `handle(User $user, string $message, string $sessionId, string $channel): array`.
  - Sanitiza input (quita PII antes de enviar a DeepSeek).
  - Clasifica intención con reglas + LLM.
  - Si intención es `faq` o `unknown`: responde usando base de conocimiento.
  - Si intención es lectura: ejecuta tool correspondiente.
  - Si intención es escritura: inicia flujo de recolección + confirmación.
- [x] Crear `App\Services\Chatbot\IntentClassifier`:
  - Primero reglas rápidas (keywords).
  - Fallback a DeepSeek con system prompt estricto de intenciones permitidas.
- [x] Crear `App\Services\Chatbot\ContextBuilder`:
  - Obtiene apartamentos del usuario (`Ownership::where('user_id')`).
  - Aplica scopes de tenant.
  - Nunca incluye datos de otros propietarios.
- [x] Crear `App\Services\Chatbot\Tools\GetInvoiceBalanceTool`.
- [x] Crear `App\Services\Chatbot\Tools\GetDueDateTool`.
- [x] Crear `App\Services\Chatbot\Tools\GetPaymentStatusTool`.
- [x] Crear `App\Services\Chatbot\Tools\GetCondoRulesTool`:
  - Lee archivos markdown de `resources/chatbot/kb/`.
- [x] Crear base de conocimiento inicial:
  - `resources/chatbot/kb/general.md`.
  - `resources/chatbot/kb/billing.md`.
  - `resources/chatbot/kb/payments.md`.
  - `resources/chatbot/kb/support.md`.
- [x] Crear `ChatbotController` + rutas:
  - `POST /chatbot/message` → recibe mensaje, devuelve respuesta JSON.
  - `GET /chatbot/history` → historial del usuario autenticado.
- [x] Crear vista `resources/views/chatbot/widget.blade.php`.
- [x] Incluir widget en `layouts.app` (solo para usuarios autenticados).
- [x] Rate limiting por usuario (`RateLimiter`).
- [x] Memoria conversacional: `HistoryLoader` carga los últimos `CHATBOT_MAX_HISTORY_MESSAGES` (por defecto 20, limitado a 10 al enviar a DeepSeek) desde `chatbot_conversations`.
- [x] Tests unitarios:
  - `IntentClassifierTest`.
  - `ContextBuilderTest`.
  - `GetInvoiceBalanceToolTest`.
  - `DeepSeekClientTest`.
  - `PiiSanitizerTest`.
  - `GuardrailsTest`.
  - `HistoryLoaderTest`.

**Entregable**: usuario puede abrir widget, preguntar “¿cuánto debo?” y recibir saldo de su apartamento; también puede preguntar FAQs del condominio. ✅

---

### Fase 2 — Acciones controladas (3–4 días)

**Objetivo**: permitir acciones de escritura con confirmación inline y auditoría.

- [x] Crear `App\Services\Chatbot\Tools\ReportPaymentTool`:
  - Recolecta: factura, monto USD/VES, fecha, método, referencia.
  - Valida que la factura pertenezca al usuario.
  - Genera resumen y pide confirmación inline.
  - Al confirmar: crea `PaymentReport` en estado `reported`.
- [x] Crear `App\Services\Chatbot\Tools\CreateSupportTicketTool`:
  - Recolecta: categoría, descripción, urgencia.
  - Crea ticket en tabla `support_tickets`.
  - Responde con número de ticket.
- [x] Crear `App\Services\Chatbot\ConfirmationManager`:
  - Guarda estado pendiente en `chatbot_conversations` (5 min).
  - Detecta respuestas de confirmación (“sí”, “confirmo”, “no”, “cancelar”).
  - Expira confirmaciones no respondidas.
- [x] Actualizar `ChatOrchestrator` para manejar multi-turn:
  - Si hay acción pendiente de confirmación, priorizarla sobre nueva intención.
  - Si faltan datos, hacer pregunta de seguimiento.
- [x] Tests de integración:
  - `ReportPaymentToolTest`.
  - `CreateSupportTicketToolTest`.
  - `ConfirmationManagerTest`.

**Entregable**: usuario puede decir “quiero reportar un pago” y el bot lo guía hasta crear el `PaymentReport`; también puede crear ticket de soporte. ✅

---

### Fase 3 — Guardrails, privacidad y fallback humano (2–3 días)

**Objetivo**: endurecer seguridad, privacidad y experiencia de escalamiento.

- [x] Implementar `App\Services\Chatbot\PiiSanitizer`:
  - Reemplaza emails, teléfonos, cédulas por tokens antes de enviar a DeepSeek.
- [x] Implementar `App\Services\Chatbot\Guardrails`:
  - Bloquea prompts de jailbreak, solicitudes fuera de dominio.
  - Límite de operaciones por sesión/usuario.
- [x] Implementar `App\Services\Chatbot\HumanHandoffService`:
  - Detecta frases de escalamiento.
  - Marca conversación como `needs_human`.
  - Notifica por email a admins.
- [x] Crear panel admin de conversaciones:
  - `GET /admin/chatbot/conversations`.
  - `GET /admin/chatbot/conversations/{conversation}`.
  - `PATCH /admin/chatbot/conversations/{conversation}/resolve`.
- [ ] Agregar métricas básicas (pendiente fase 3).
- [x] Actualizar backlog `mejoras_futuras.txt` marcando items de chatbot como hechos.

**Entregable**: chatbot con sanitización de PII, guardrails de dominio, detección de escalamiento y panel de supervisión para admins. ✅ (parcial: métricas pendientes)

---

### Fase 4 — Canales adicionales y optimización (futuro)

**Objetivo**: extender a WhatsApp y mejorar calidad.

- [ ] Crear `App\Services\Chatbot\Adapters\WhatsAppAdapter`:
  - Normaliza mensajes entrantes de webhook a formato interno.
  - Identifica usuario por teléfono (requiere vincular teléfono a `users`).
- [ ] Crear `App\Services\Chatbot\Adapters\SmsAdapter` (opcional).
- [ ] Unificar plantillas conversacionales para web/WhatsApp/SMS.
- [ ] Implementar embeddings + búsqueda semántica en base de conocimiento (DeepSeek embeddings u otro proveedor).
- [ ] Métricas avanzadas: tasa de resolución, satisfacción, tiempo de respuesta.
- [ ] A/B testing de prompts versionados.

**Entregable**: chatbot disponible por WhatsApp con la misma lógica de negocio.

---

## 5. Estructura de archivos propuesta

```
app/
  Services/
    Chatbot/
      DeepSeekClient.php
      ChatOrchestrator.php
      ChatbotConfig.php
      IntentClassifier.php
      ContextBuilder.php
      PiiSanitizer.php
      Guardrails.php
      ConfirmationManager.php
      HumanHandoffService.php
      Adapters/
        WebAdapter.php
        WhatsAppAdapter.php (futuro)
      Tools/
        ToolInterface.php
        ToolResult.php
        GetInvoiceBalanceTool.php
        GetDueDateTool.php
        GetPaymentStatusTool.php
        GetCondoRulesTool.php
        ReportPaymentTool.php
        CreateSupportTicketTool.php
  Http/
    Controllers/
      ChatbotController.php
      ChatbotAdminController.php
  Models/
    ChatbotConversation.php
    ChatbotKnowledgeChunk.php (futuro)
    SupportTicket.php (si no existe)
resources/
  views/
    chatbot/
      widget.blade.php
      admin/
        conversations.blade.php
        conversation.blade.php
  chatbot/
    kb/
      general.md
      billing.md
      payments.md
      support.md
    prompts/
      system.txt
      intent_classification.txt
      confirmation.txt
database/migrations/
  (landlord + tenant)
  2026_07_17_000001_create_chatbot_conversations_table.php
  2026_07_17_000002_create_chatbot_knowledge_chunks_table.php
  2026_07_17_000003_create_support_tickets_table.php (si no existe)
routes/
  web.php → agregar grupo /chatbot
config/
  services.php → sección deepseek
.env.example → DEEPSEEK_*
tests/
  Unit/
    Chatbot/
      DeepSeekClientTest.php
      IntentClassifierTest.php
      ContextBuilderTest.php
      ConfirmationManagerTest.php
      PiiSanitizerTest.php
      GuardrailsTest.php
    ChatbotTools/
      GetInvoiceBalanceToolTest.php
      ReportPaymentToolTest.php
      CreateSupportTicketToolTest.php
```

---

## 6. Seguridad y privacidad (checklist)

- [x] `DEEPSEEK_API_KEY` solo en `.env`, nunca en repositorio.
- [x] Validar que la key exista al arrancar (`DeepSeekClient` lanza excepción si falta).
- [x] Sanitizar PII antes de enviar a DeepSeek.
- [x] Nunca enviar SQL ni permitir que el LLM construya consultas libres (bloqueado por `Guardrails`).
- [x] Todas las tools usan repositories con scopes de tenant y ownership.
- [x] Confirmación explícita para escritura.
- [x] Rate limiting por usuario y por condominio.
- [x] Auditoría completa de conversaciones y acciones.
- [x] Logs sin PII.
- [ ] Revisar DPA de DeepSeek antes de producción.
- [x] Fallback a humano para casos críticos o de baja confianza.

---

## 7. Costos estimados

| Concepto | Estimación mensual (USD) |
|----------|--------------------------|
| DeepSeek API (200 usuarios activos, ~10 msgs/usuario/mes) | $5 – $25 |
| Almacenamiento de conversaciones | negligible |
| WhatsApp Business API (futuro) | ~$0.005–0.08 por conversación |
| Total fase 1 | $5 – $25 |

---

## 8. Métricas de éxito

- Tiempo de respuesta promedio < 3 segundos.
- Tasa de escalamiento a humano < 15 % en el primer mes.
- Cobertura de intenciones: FAQ, saldo, vencimiento, estado de pago, reportar pago, crear ticket.
- Cero fugas de datos entre usuarios/condominios en tests de aislamiento.
- 100 % de acciones de escritura auditadas.

---

## 9. Estado actual resumido

| Fase | Estado |
|------|--------|
| Fase 0 — Configuración | ✅ Completada |
| Fase 1 — Motor + FAQ | ✅ Completada |
| Fase 2 — Acciones controladas | ✅ Completada |
| Fase 3 — Guardrails + privacidad + humano | ✅ Completada (métricas pendientes) |
| Fase 3.1 — Memoria conversacional | ✅ Completada |
| Fase 4 — WhatsApp + optimización | ⏳ Futura |

**Tests**: 27 passed, 60 assertions.  
**Ping a DeepSeek**: funcionando.  
**Backlog**: `mejoras_futuras.txt` actualizado con items de chatbot marcados como hechos.

---

## 10. Próximos pasos inmediatos

1. Implementar métricas básicas (tasa de escalamiento, intenciones frecuentes, volumen de mensajes).
2. Probar flujo end-to-end del widget web en navegador.
3. Ajustar base de conocimiento con reglas reales del condominio.
4. Planificar Fase 4 (WhatsApp) si se desea extender el canal.

---

## 11. Dudas pendientes a resolver con el usuario

- ¿Quieres que el widget esté disponible para **todos los roles** (incluido super admin) o solo para propietarios/residentes?
- ¿Prefieres que la base de conocimiento se edite solo por archivos markdown en el repo o también quieres un CRUD admin para editarla desde el panel?
- ¿Hay reglas específicas del condominio Los Robles que deba incluir en la KB inicial?
- ¿Deseas implementar ahora el dashboard de métricas del chatbot?
