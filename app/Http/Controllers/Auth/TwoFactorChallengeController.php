<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        $user = $this->challengedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        return view('auth.two-factor-challenge', ['method' => $user->two_factor_method]);
    }

    public function verify(Request $request)
    {
        $user = $this->challengedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => 'required|string|max:10',
        ]);

        $throttleKey = '2fa|'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['code' => 'Demasiados intentos. Inténtalo de nuevo en '.$seconds.' segundos.']);
        }

        $code = preg_replace('/\s+/', '', $data['code']);
        $valid = $user->two_factor_method === 'totp'
            ? $user->verifyTotpCode($code)
            : $user->verifyTwoFactorCode($code);

        if (!$valid) {
            RateLimiter::hit($throttleKey, 60);
            Log::warning('Código 2FA inválido', ['user_id' => $user->id, 'ip' => $request->ip()]);
            return back()->withErrors(['code' => 'Código inválido o expirado.']);
        }

        RateLimiter::clear($throttleKey);
        $remember = (bool) $request->session()->pull('2fa.remember', false);
        $request->session()->forget('2fa.user_id');

        Auth::loginUsingId($user->id, $remember);
        $request->session()->regenerate();

        return redirect()->intended('/invoices');
    }

    public function resend(Request $request)
    {
        $user = $this->challengedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        if ($user->two_factor_method !== 'email') {
            return back()->withErrors(['code' => 'Este método no usa códigos por correo.']);
        }

        $throttleKey = '2fa-resend|'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['code' => 'Espera '.$seconds.' segundos antes de pedir otro código.']);
        }
        RateLimiter::hit($throttleKey, 60);

        $code = $user->generateTwoFactorCode();
        Mail::to($user->email)->send(new TwoFactorCodeMail($code, $user->first_name ?? $user->name ?? ''));

        return back()->with('status', 'Te enviamos un nuevo código a tu correo.');
    }

    protected function challengedUser(Request $request): ?User
    {
        $id = $request->session()->get('2fa.user_id');
        if (!$id) { return null; }
        $user = User::find($id);
        return ($user && $user->twoFactorEnabled()) ? $user : null;
    }
}
