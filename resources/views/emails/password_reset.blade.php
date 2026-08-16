<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupera tu contraseña</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:#0d6efd; padding:30px 40px; text-align:center;">
                            <h1 style="color:#ffffff; margin:0; font-size:24px; font-weight:600;">
                                <i class="bi bi-key" style="margin-right:8px;"></i>Recupera tu contraseña
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px; color:#333333; font-size:16px; line-height:1.6;">
                            <p style="margin-top:0;">Hola {{ $user->first_name ?? $user->name ?? '' }}, recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>{{ config('app.name', 'Los Robles') }}</strong>.</p>

                            <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>

                            <p style="text-align:center; margin:32px 0;">
                                <a href="{{ $resetUrl }}" style="display:inline-block; background:#0d6efd; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:8px; font-weight:600; font-size:16px;">Restablecer contraseña</a>
                            </p>

                            <p style="margin-bottom:8px;">Si no puedes usar el botón, copia y pega este enlace en tu navegador:</p>
                            <p style="word-break:break-all; color:#0d6efd; font-size:14px;">{{ $resetUrl }}</p>

                            <p style="color:#666666; font-size:14px; margin-top:24px;">Este enlace expira en <strong>{{ $expireMinutes }} minutos</strong>. Si no solicitaste este cambio, ignora este correo; tu contraseña actual seguirá siendo segura.</p>

                            <hr style="border:none; border-top:1px solid #e9ecef; margin:32px 0;">

                            <p style="color:#999999; font-size:13px; margin:0;">Si tienes dudas, contacta al administrador de tu condominio.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
