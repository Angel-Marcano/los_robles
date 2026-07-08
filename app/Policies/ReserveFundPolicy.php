<?php
namespace App\Policies;

use App\Models\User;

class ReserveFundPolicy
{
    public function viewAny(User $user){ return $user->hasRole('super_admin'); }
    public function view(User $user){ return $user->hasRole('super_admin'); }
    public function manage(User $user){ return $user->hasRole('super_admin'); }
}
