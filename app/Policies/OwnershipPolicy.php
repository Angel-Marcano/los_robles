<?php
namespace App\Policies; use App\Models\{User, Ownership, Apartment};
class OwnershipPolicy {
    public function viewAny(User $user){ return $user->hasRole('super_admin')||$user->hasRole('condo_admin')||$user->hasRole('tower_admin'); }
    public function create(User $user, Apartment $ap){ return $this->viewAny($user); }
    public function delete(User $user, Ownership $own){ return $this->viewAny($user); }
    public function toggle(User $user, Ownership $own){ return $this->viewAny($user); }
}
