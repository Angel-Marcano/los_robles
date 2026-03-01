<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'            => 'required|string|max:120',
            'first_name'      => 'nullable|string|max:80',
            'last_name'       => 'nullable|string|max:120',
            'document_type'   => 'nullable|in:cedula,pasaporte',
            'document_number' => 'nullable|string|max:40',
            'email'           => 'required|email|unique:tenant.users,email,' . $user->id,
        ]);

        $user->update($data);

        return redirect()->route('profile.edit')->with('status', 'Datos actualizados correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.edit')->with('status', 'Contraseña actualizada correctamente.');
    }
}
