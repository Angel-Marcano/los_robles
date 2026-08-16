<?php
namespace App\Http\Controllers;

use App\Mail\TwoFactorCodeMail;
use App\Services\AuditService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorSettingsController extends Controller
{
    /**
     * Inicia la activación por correo: envía un código al email del usuario.
     */
    public function enableEmail(Request $request)
    {
        $user = auth()->user();
        if ($user->twoFactorEnabled()) {
            return back()->withErrors(['two_factor' => 'La verificación en dos pasos ya está activa.']);
        }

        $user->forceFill([
            'two_factor_method' => 'email',
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $code = $user->generateTwoFactorCode();
        Mail::to($user->email)->send(new TwoFactorCodeMail($code, $user->first_name ?? $user->name ?? ''));

        return back()->with('status', 'Te enviamos un código a tu correo. Ingrésalo abajo para confirmar.');
    }

    /**
     * Inicia la activación por app: genera el secreto TOTP y muestra el QR.
     */
    public function enableTotp(Request $request)
    {
        $user = auth()->user();
        if ($user->twoFactorEnabled()) {
            return back()->withErrors(['two_factor' => 'La verificación en dos pasos ya está activa.']);
        }

        $secret = app(Google2FA::class)->generateSecretKey();
        $user->forceFill([
            'two_factor_method' => 'totp',
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_code' => null,
            'two_factor_code_expires_at' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'Escanea el código QR con tu app autenticadora y confirma con un código.');
    }

    /**
     * Confirma la activación (email o totp) validando un código.
     */
    public function confirm(Request $request)
    {
        $user = auth()->user();
        if ($user->twoFactorEnabled()) {
            return back()->withErrors(['two_factor' => 'La verificación en dos pasos ya está activa.']);
        }
        if (!$user->two_factor_method) {
            return back()->withErrors(['two_factor' => 'Primero elige un método de verificación.']);
        }

        $data = $request->validate(['code' => 'required|string|max:10']);
        $code = preg_replace('/\s+/', '', $data['code']);

        $valid = $user->two_factor_method === 'totp'
            ? $user->verifyTotpCode($code)
            : $user->verifyTwoFactorCode($code);

        if (!$valid) {
            return back()->withErrors(['two_factor' => 'Código inválido o expirado.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        app(AuditService::class)->log('2fa_enabled', 'User', $user->id, ['method' => $user->two_factor_method]);

        return back()->with('status', 'Verificación en dos pasos activada.');
    }

    /**
     * Cancela una activación pendiente (sin confirmar).
     */
    public function cancel(Request $request)
    {
        $user = auth()->user();
        if ($user->twoFactorEnabled()) {
            return back()->withErrors(['two_factor' => 'Para desactivar usa el botón Desactivar con tu contraseña.']);
        }
        $this->clearTwoFactor($user);
        return back()->with('status', 'Activación cancelada.');
    }

    /**
     * Desactiva el 2FA (requiere contraseña actual).
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required']);
        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['two_factor' => 'La contraseña no es correcta.']);
        }

        $this->clearTwoFactor($user);
        app(AuditService::class)->log('2fa_disabled', 'User', $user->id);

        return back()->with('status', 'Verificación en dos pasos desactivada.');
    }

    /**
     * Genera el SVG del QR para el secreto TOTP pendiente del usuario.
     */
    public static function totpQrSvg($user): ?string
    {
        if ($user->two_factor_method !== 'totp' || !$user->two_factor_secret) { return null; }
        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
        } catch (\Throwable $e) {
            return null;
        }
        $otpauth = app(Google2FA::class)->getQRCodeUrl(config('app.name', 'Los Robles'), $user->email, $secret);
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($otpauth);
        // Quitar la declaración XML para poder incrustar el SVG inline en el HTML.
        return preg_replace('/^<\?xml.*?\?>\s*/', '', $svg);
    }

    /**
     * Devuelve el secreto TOTP en claro (para ingreso manual en la app).
     */
    public static function totpSecret($user): ?string
    {
        if ($user->two_factor_method !== 'totp' || !$user->two_factor_secret) { return null; }
        try {
            return Crypt::decryptString($user->two_factor_secret);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function clearTwoFactor($user): void
    {
        $user->forceFill([
            'two_factor_method' => null,
            'two_factor_secret' => null,
            'two_factor_code' => null,
            'two_factor_code_expires_at' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
