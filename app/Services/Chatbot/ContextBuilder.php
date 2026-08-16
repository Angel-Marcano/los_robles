<?php

namespace App\Services\Chatbot;

use App\Models\User;
use App\Models\Ownership;
use App\Models\Apartment;

class ContextBuilder
{
    /**
     * Construye contexto seguro del usuario para enviar al LLM.
     * Nunca incluye datos de otros usuarios ni apartamentos.
     */
    public function build(User $user): array
    {
        $apartmentIds = Ownership::where('user_id', $user->id)
            ->where('active', true)
            ->pluck('apartment_id')
            ->toArray();

        $apartments = Apartment::whereIn('id', $apartmentIds)
            ->with('tower')
            ->get(['id', 'code', 'tower_id']);

        $apartmentList = [];
        foreach ($apartments as $apt) {
            $apartmentList[] = [
                'id' => $apt->id,
                'code' => $apt->code,
                'tower' => optional($apt->tower)->name,
            ];
        }

        $isAdmin = $user->hasRole('super_admin')
            || $user->hasRole('condo_admin')
            || $user->hasRole('tower_admin');

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'is_admin' => $isAdmin,
            'apartments' => $apartmentList,
            'default_apartment_id' => $apartmentList[0]['id'] ?? null,
        ];
    }
}
