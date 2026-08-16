<div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto;">
	<h2 style="color:#333;">Código de verificación</h2>
	@if($userName)
	<p>Hola {{ $userName }},</p>
	@endif
	<p>Usa el siguiente código para completar tu inicio de sesión:</p>
	<p style="font-size: 32px; font-weight: bold; letter-spacing: 8px; text-align: center; background:#f4f4f4; padding: 16px; border-radius: 8px;">{{ $code }}</p>
	<p style="color:#666;">Este código expira en <strong>10 minutos</strong>. Si no intentaste iniciar sesión, ignora este correo y considera cambiar tu contraseña.</p>
</div>
