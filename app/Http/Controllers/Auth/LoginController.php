<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Rate limiting por email+IP: máx 5 intentos fallidos por minuto.
        $throttleKey = $this->throttleKey($request);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning('Login bloqueado por rate limit', [
                'host' => $request->getHost(),
                'ip' => $request->ip(),
            ]);
            return back()->withErrors([
                'email' => 'Demasiados intentos de inicio de sesión. Inténtalo de nuevo en '.$seconds.' segundos.',
            ])->withInput();
        }

        if (Auth::attempt($data)) {
            RateLimiter::clear($throttleKey);

            $user = Auth::user();
            if ($user->twoFactorEnabled()) {
                // No persistir la sesión autenticada: pasar al desafío 2FA.
                $remember = $request->boolean('remember');
                Auth::logout();
                $request->session()->regenerate();
                $request->session()->put('2fa.user_id', $user->id);
                $request->session()->put('2fa.remember', $remember);

                if ($user->two_factor_method === 'email') {
                    $code = $user->generateTwoFactorCode();
                    \Illuminate\Support\Facades\Mail::to($user->email)
                        ->send(new \App\Mail\TwoFactorCodeMail($code, $user->first_name ?? $user->name ?? ''));
                }

                return redirect()->route('2fa.challenge');
            }

            $request->session()->regenerate();
            return redirect()->intended('/invoices');
        }

        RateLimiter::hit($throttleKey, 60);

        Log::warning('Intento de login fallido', [
            'host' => $request->getHost(),
            'ip' => $request->ip(),
        ]);

        return back()->withErrors(['email' => 'Credenciales inválidas'])->withInput();
    }

    protected function throttleKey(Request $request): string
    {
        return 'login|'.Str::lower((string) $request->input('email')).'|'.$request->ip();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
