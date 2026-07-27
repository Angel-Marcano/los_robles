<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalConsentController extends Controller
{
    /**
     * Muestra la página para aceptar privacidad y términos si el usuario
     * aún no los ha aceptado (o la versión vigente es mayor).
     */
    public function showAccept()
    {
        $user = Auth::user();
        $currentVersion = config('app.legal_version', '1.0');

        $needsPrivacy = !$user->accepted_privacy_at || $user->legal_version !== $currentVersion;
        $needsTerms = !$user->accepted_terms_at || $user->legal_version !== $currentVersion;

        if (!$needsPrivacy && !$needsTerms) {
            return redirect()->intended('/invoices');
        }

        return view('legal.accept', compact('currentVersion', 'needsPrivacy', 'needsTerms'));
    }

    /**
     * Guarda el consentimiento del usuario.
     */
    public function accept(Request $request)
    {
        $request->validate([
            'accept_privacy' => 'required_without:accept_terms|accepted',
            'accept_terms'   => 'required_without:accept_privacy|accepted',
        ], [
            'accept_privacy.accepted' => 'Debes aceptar la Política de Privacidad.',
            'accept_terms.accepted'   => 'Debes aceptar los Términos y Condiciones.',
        ]);

        $user = Auth::user();
        $currentVersion = config('app.legal_version', '1.0');

        $updates = ['legal_version' => $currentVersion];
        if ($request->boolean('accept_privacy')) {
            $updates['accepted_privacy_at'] = now();
        }
        if ($request->boolean('accept_terms')) {
            $updates['accepted_terms_at'] = now();
        }

        $user->forceFill($updates)->save();

        return redirect()->intended('/invoices')->with('status', 'Consentimiento registrado. ¡Bienvenido!');
    }
}