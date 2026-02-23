<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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
        // Pre diagnóstico
        $tenantDb = config('database.connections.tenant.database') ?? null;
        $userPreview = \App\Models\User::limit(5)->get(['id','email'])->toArray();
        \Log::info('Pre login diagnostics', [
            'host' => $request->getHost(),
            'tenant_db' => $tenantDb,
            'submitted_email' => $data['email'],
            'users_sample' => $userPreview,
            'users_count' => \App\Models\User::count(),
        ]);
        // Intento estándar de Auth (usará el modelo con trait para conexión tenant)
        if (Auth::attempt($data)) {
            $request->session()->regenerate();
            Log::info('Login correcto via Auth::attempt', ['email'=>$data['email']]);
            return redirect()->intended('/invoices');
        }
        // Fallback manual: buscar usuario en conexión tenant y validar hash
        try {
            $user = User::where('email',$data['email'])->first();
            $hashOk = $user ? Hash::check($data['password'], $user->password) : false;
            Log::info('Login fallback check', [
                'email' => $data['email'],
                'user_found' => (bool)$user,
                'tenant_db' => config('database.connections.tenant.database') ?? null,
                'hash_ok' => $hashOk,
            ]);
            if ($hashOk) {
                Auth::login($user, false);
                $request->session()->regenerate();
                Log::info('Login correcto via fallback manual', ['email'=>$data['email']]);
                return redirect()->intended('/invoices');
            }
            Log::warning('Login fallido definitivo', [
                'email' => $data['email'],
                'user_found' => (bool)$user,
                'tenant_db' => config('database.connections.tenant.database') ?? null,
                'hash_ok' => $hashOk,
            ]);
        } catch (\Throwable $e) {
            Log::error('Excepción login fallback', ['error'=>$e->getMessage()]);
        }
        return back()->withErrors(['email' => 'Credenciales inválidas'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
