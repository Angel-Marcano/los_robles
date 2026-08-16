<?php

namespace App\Policies;

use App\Models\{User, Invoice, PaymentReport, Ownership};

class CommentPolicy
{
    protected function userApartmentIds(User $user)
    {
        return Ownership::where('user_id', $user->id)->pluck('apartment_id');
    }

    protected function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'condo_admin', 'tower_admin']);
    }

    public function comment(User $user, $entity)
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        // Propietario/residente: solo si la factura/pago pertenece a su apartamento
        if ($entity instanceof Invoice) {
            if (!$entity->apartment_id) {
                return false;
            }
            return $this->userApartmentIds($user)->contains($entity->apartment_id);
        }

        if ($entity instanceof PaymentReport) {
            $apartmentId = $entity->apartment_id ?? optional($entity->invoice)->apartment_id;
            if (!$apartmentId) {
                return false;
            }
            return $this->userApartmentIds($user)->contains($apartmentId);
        }

        return false;
    }

    public function view(User $user, $entity)
    {
        return $this->comment($user, $entity);
    }
}
