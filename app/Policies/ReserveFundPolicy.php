<?php
namespace App\Policies;

use App\Models\User;

class ReserveFundPolicy
{
    public function viewAny(User $user){ return $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin'); }
    public function view(User $user){ return $this->viewAny($user); }
    public function manage(User $user){ return $user->hasRole('super_admin') || $user->hasRole('condo_admin'); }
}
