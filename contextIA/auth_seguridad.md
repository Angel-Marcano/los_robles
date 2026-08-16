# Módulo: Autenticación y Seguridad

> Archivo de contexto para IA. Sistema Los Robles — Laravel multi-tenant.

## Resumen

Login con **verificación en dos pasos (2FA)** opcional (por correo o app TOTP),
recuperación de contraseña, gestión de perfil y activación/desactivación de 2FA.

## Login

### `Auth\LoginController` (en `app/Http/Controllers/Auth/`)
- `showLogin` / `login` (middleware `guest`, `throttle:login`).
- `logout`.
- Tras validar credenciales, si el usuario tiene 2FA habilitado, redirige al **desafío 2FA**
  (guarda estado en sesión, no autentica todavía).

### `Auth\TwoFactorChallengeController`
- `show` — formulario del código.
- `verify` — valida código (email o TOTP); autentica si es correcto.
- `resend` — reenvía código por correo.

## 2FA — `User` model
- `twoFactorEnabled()` → `two_factor_method !== null && two_factor_confirmed_at !== null`.
- `generateTwoFactorCode()` — código 6 dígitos, hasheado, expira 10 min. Devuelve el claro
  para enviar por correo.
- Métodos: `email` (código por correo) o `totp` (app autenticadora).

## 2FA — `TwoFactorSettingsController` (perfil)
- `enableEmail(Request)`:
  - Si ya habilitado, error.
  - Setea `two_factor_method='email'`, limpia secret, no confirmado.
  - Genera código y envía `TwoFactorCodeMail`.
- `enableTotp(Request)`:
  - Genera secreto TOTP (Google2FA), lo encripta (`Crypt::encryptString`).
  - Setea `two_factor_method='totp'`, guarda secret, no confirmado.
  - Muestra QR para escanear.
- `confirm(Request)`:
  - Valida código (email o TOTP según método).
  - Marca `two_factor_confirmed_at=now()` → 2FA activo.

## Recuperación de contraseña — `PasswordResetController`
- `showForgot()` — formulario "olvidé mi contraseña".
- `sendLink(Request)`:
  - Valida `email`. Si existe usuario, genera token (60 chars) y guarda en
    `password_resets` (conexión tenant).
  - Envía correo con enlace `url('/password/reset/{token}')`.
  - Respuesta genérica (no revela si el email existe).
- `showReset($token)` — formulario de nueva contraseña.
- `performReset(Request)`:
  - Valida `token` y `password` (min 6, confirmed).
  - Busca token en `password_resets`; actualiza password (Hash); borra token.
  - Redirige a login.

## Perfil — `ProfileController`
- `edit()` / `update(Request)` — datos: `name`, `first_name`, `last_name`, `document_type`,
  `document_number`, `email` (unique en tenant excepto propio).
- `updatePassword(Request)` — valida `current_password` y nueva (min 6, confirmed).

## Rutas (`routes/web.php`)
- `login`, `login.perform`, `logout`.
- `2fa.challenge`, `2fa.verify`, `2fa.resend`.
- `profile.edit`, `profile.update`, `profile.password`.
- `profile.2fa.email`, `profile.2fa.totp`, `profile.2fa.confirm`.

## Vistas
- `resources/views/auth/` — login, forgot, reset, 2fa challenge.
- `resources/views/profile/` — edit.

## Reglas de negocio clave

1. **2FA opcional** por usuario; se activa desde el perfil y requiere confirmación.
2. **Email**: código de 6 dígitos con expiración 10 min.
3. **TOTP**: secreto encriptado en BD; QR generado con BaconQrCode.
4. **Throttle** en login y 2FA (`throttle:login`).
5. **Reset por tenant**: la tabla `password_resets` vive en la conexión tenant.
6. **No revelar** si un email existe al recuperar contraseña.

## Casos de uso típicos
- Usuario activa 2FA por correo → recibe código → confirma.
- Usuario olvida contraseña → recibe enlace → resetea.
- Login con 2FA: credenciales → desafío → autenticación.

## Notas
- `document_type` limitado a `cedula`/`pasaporte`.
- El secreto TOTP se encripta con `APP_KEY` (Crypt).
- No hay rate limiting específico para activación de 2FA (solo el de login).