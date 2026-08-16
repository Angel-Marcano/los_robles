<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireLegalConsent
{
    /**
     * Si el usuario autenticado no ha aceptado la versión vigente de los
     * documentos legales, redirige a la página de consentimiento.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $currentVersion = config('app.legal_version', '1.0');

        $needsConsent = (!$user->accepted_privacy_at || !$user->accepted_terms_at || $user->legal_version !== $currentVersion);

        if ($needsConsent && !$request->routeIs('legal.accept*') && !$request->routeIs('legal.*') && !$request->is('logout')) {
            return redirect()->route('legal.accept');
        }

        return $next($request);
    }
}