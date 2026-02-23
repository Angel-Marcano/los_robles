<?php
namespace App\Policies; use App\Models\User; use App\Models\ExpenseItem;
class ExpenseItemPolicy { public function viewAny(User $user){ return $user->hasRole('super_admin'); } public function create(User $user){ return $user->hasRole('super_admin'); } public function update(User $user, ExpenseItem $item){ return $user->hasRole('super_admin'); } public function delete(User $user, ExpenseItem $item){ return $user->hasRole('super_admin'); } }
