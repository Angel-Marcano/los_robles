# Módulo: Integraciones Externas

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Servicios externos y de infraestructura: SMS (EnviaTuSMS), LLM (DeepSeek), correo y
almacenamiento de archivos.

## EnviaTuSMS — `App\Services\EnviatuSmsService`

> Integración base documentada en `/memories/repo/integrations.md`.

- Cliente HTTP para la API de EnviaTuSMS (envío de SMS, consulta de saldo).
- `request(string $endpoint, array $query, array $json, string $method)`:
  - Agrega `api_key` como query param.
  - Base URL configurable; timeout y retry configurables.
  - Usa `Http::acceptJson()->timeout()->retry()`.
- `getBalance()` → endpoint `balance`.
- `sendSms(array $payload)` → endpoint `sms/send` (POST).

### Config (`config/services.php` → `services.enviatusms.*`)
- `api_key` (env `ENVIATUSMS_API_KEY`) — **requerido**.
- `base_url` (default `https://www.enviatusms.com/api`).
- `endpoint_balance` (default `balance`), `endpoint_send` (default `sms/send`).
- `timeout` (default 10), `retry_times` (default 1), `retry_sleep_ms` (default 200).

### Tests
- `tests/Feature/EnviatuSmsServiceTest.php` — usa `Http::fake`.

## DeepSeek — `App\Services\Chatbot\DeepSeekClient`

> Detalle completo en `chatbot.md`.

- SDK OpenAI apuntando a `https://api.deepseek.com`.
- Env `DEEPSEEK_API_KEY` (validada al instanciar).
- Modelo default `deepseek-chat`; `deepseek-reasoner` para casos complejos.
- Config en `config/services.php` → `services.deepseek.*` (`api_key`, `base_url`,
  `model`, `temperature`, `max_tokens`, `timeout`).

## Correo

### Mailables (`app/Mail/`)
- `InvoiceCreatedMail` — notifica al propietario su sub-factura generada (cola).
- `InvoiceReminderMail` — recordatorio de pago (`reminder_sent_at` en Invoice).
- `TwoFactorCodeMail` — código 2FA por correo.
- `ChatbotHandoffMail` — escalamiento a humano desde el chatbot.

### Config
- `config/mail.php` — driver SMTP (o cola). Envío en cola (`->queue()`).

## Almacenamiento de comprobantes — `PaymentAttachmentStorageService`

> Detalle en `cobro.md`.

- Disco configurable: `config('filesystems.payment_attachments.disk', 'public')`.
- Si es `s3` y no hay config, cae a disco local fallback.
- Ruta: `payment-attachments/{condo}/{invoice}/{filename}`.
- Resuelve URLs históricas (compatibilidad con paths viejos).

## API REST — `routes/api.php`

> Autenticación Sanctum + rate limit (`api.rate`).

- `POST /api/login` — login API (token).
- `GET /api/me` — usuario actual.
- `POST /api/logout`.
- `GET /api/invoices`, `GET /api/invoices/{invoice}` — facturas.
- `GET /api/accounts` — cuentas.
- `GET /api/rates/current` — tasa activa.

### Controladores API (`app/Http/Controllers/Api/`)
- `AuthController`, `InvoiceApiController`, `AccountApiController`, `RateApiController`.

## Reglas de negocio clave

1. **API keys en env**, nunca en código. Validadas al arrancar el servicio.
2. **Timeouts y retries** configurables para servicios externos.
3. **Cola** para correos (no bloquear el request).
4. **Fallback** de almacenamiento S3 → local para no perder comprobantes.
5. **Rate limit** en API REST (`api.rate` middleware).

## Casos de uso típicos
- Enviar SMS de recordatorio de pago (futuro).
- Notificar por correo la creación de sub-facturas.
- App móvil consume API REST con token Sanctum.
- Subir comprobante de pago a S3 (o local si S3 falla).

## Notas / deudas
- SMS aún no integrado en flujos (solo el servicio base).
- API REST mínima (solo lectura de facturas/cuentas/tasa).