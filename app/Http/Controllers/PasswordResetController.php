<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Minutos de validez del token de recuperación.
     * Debe coincidir con config/auth.php passwords.users.expire.
     */
    private const TOKEN_EXPIRE_MINUTES = 60;

    /**
     * Nombre de la conexión de base de datos tenant, o null si no aplica.
     */
    private function tenantDb(): ?string
    {
        return app()->bound('currentCondominium') && config('database.connections.tenant') ? 'tenant' : null;
    }

    /**
     * Muestra el formulario para solicitar el enlace de recuperación.
     */
    public function showForgot()
    {
        return view('auth.forgot');
    }

    /**
     * Envía el enlace de recuperación al correo indicado.
     * Siempre responde con el mismo mensaje para no revelar existencia del email.
     */
    public function sendLink(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower($data['email']);

        // Rate limiting: máximo 3 solicitudes por email cada 60 minutos.
        $throttleKey = 'password.forgot|' . $email . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning('Password reset bloqueado por rate limit', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return back()
                ->withErrors(['email' => 'Demasiados intentos. Inténtalo de nuevo en ' . ceil($seconds / 60) . ' minutos.'])
                ->withInput();
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->sendResetLink($user);
        }

        RateLimiter::hit($throttleKey, 60 * 60);

        return back()
            ->with('status', 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.')
            ->withInput();
    }

    /**
     * Genera un token, lo almacena hasheado y envía el correo con el enlace en claro.
     */
    private function sendResetLink(User $user): void
    {
        $plainToken = Str::random(64);
        $hashedToken = Hash::make($plainToken);

        DB::connection($this->tenantDb())
            ->table('password_resets')
            ->updateOrInsert(
                ['email' => $user->email],
                ['token' => $hashedToken, 'created_at' => now()]
            );

        $resetUrl = url('/password/reset/' . $plainToken . '?email=' . urlencode($user->email));

        try {
            Mail::to($user->email)
                ->send(new PasswordResetMail($user, $resetUrl));
        } catch (\Exception $e) {
            Log::error('Error enviando correo de recuperación de contraseña', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Muestra el formulario para restablecer la contraseña.
     */
    public function showReset(Request $request, string $token)
    {
        $email = $request->input('email', '');

        if (! $this->tokenIsValid($token, $email)) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.']);
        }

        return view('auth.reset', compact('token', 'email'));
    }

    /**
     * Procesa el restablecimiento de contraseña.
     */
    public function performReset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], [
            'password.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula y un número.',
        ]);

        $email = Str::lower($data['email']);

        if (! $this->tokenIsValid($data['token'], $email)) {
            return back()
                ->withErrors(['token' => 'El enlace de recuperación es inválido o ha expirado.'])
                ->withInput();
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'No encontramos una cuenta con ese correo.'])
                ->withInput();
        }

        $user->update(['password' => Hash::make($data['password'])]);

        $this->deleteResetToken($email);

        Log::info('Contraseña restablecida exitosamente', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return redirect()->route('login')
            ->with('status', 'Tu contraseña ha sido actualizada. Inicia sesión con tu nueva contraseña.');
    }

    /**
     * Verifica que el token coincida con el hash almacenado y no haya expirado.
     */
    private function tokenIsValid(string $plainToken, string $email): bool
    {
        if (empty($email) || empty($plainToken)) {
            return false;
        }

        $record = DB::connection($this->tenantDb())
            ->table('password_resets')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return false;
        }

        if (now()->diffInMinutes($record->created_at) > self::TOKEN_EXPIRE_MINUTES) {
            return false;
        }

        return Hash::check($plainToken, $record->token);
    }

    /**
     * Elimina el token de recuperación una vez usado.
     */
    private function deleteResetToken(string $email): void
    {
        DB::connection($this->tenantDb())
            ->table('password_resets')
            ->where('email', $email)
            ->delete();
    }
}

