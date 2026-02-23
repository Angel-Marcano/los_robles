<?php
namespace App\Policies;
use App\Models\{User, Invoice, Ownership, Apartment};
class InvoicePolicy {
    protected function userApartmentIds(User $user){ return Ownership::where('user_id',$user->id)->pluck('apartment_id'); }
    public function create(User $user){ return $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin'); }
    public function store(User $user){ return $this->create($user); }
    public function markPaid(User $user, Invoice $invoice){ return $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin'); }
    public function update(User $user, Invoice $invoice){
        // Editar solo cuando está en borrador
        if($invoice->status !== 'draft') return false;
        // Admins del condominio/torre y el creador pueden editar
        if($user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin')) return true;
        return $invoice->created_by === $user->id;
    }
    public function view(User $user, Invoice $invoice){
        if($user->hasRole('super_admin')) return true;
        $apartmentIds = $this->userApartmentIds($user);
        // Facturas de torre: restringidas a tower_admin y residentes de esa torre; condo_admin NO las ve.
        if($invoice->tower_id){
            if($user->hasRole('tower_admin')) return true;
            $towerApartmentIds = Apartment::where('tower_id',$invoice->tower_id)->pluck('id');
            return $apartmentIds->intersect($towerApartmentIds)->isNotEmpty() || $invoice->created_by == $user->id;
        }
        // Facturas de condominio: condo_admin y residentes del condominio
        if($user->hasRole('condo_admin')) return true;
        $condoApartmentIds = Apartment::where('condominium_id',$invoice->condominium_id)->pluck('id');
        return $apartmentIds->intersect($condoApartmentIds)->isNotEmpty() || $invoice->created_by == $user->id;
    }
}
