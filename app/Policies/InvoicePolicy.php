<?php
namespace App\Policies;
use App\Models\{User, Invoice, Ownership, Apartment};
class InvoicePolicy {
    protected function userApartmentIds(User $user){ return Ownership::where('user_id',$user->id)->pluck('apartment_id'); }
    public function viewAny(User $user){ return true; }
    public function create(User $user){ return $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin'); }
    public function store(User $user){ return $this->create($user); }
    public function markPaid(User $user, Invoice $invoice){ return $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin'); }
    public function void(User $user, Invoice $invoice){
        // Solo admins pueden anular/reemitir facturas aprobadas/pagadas.
        if(!in_array((string)$invoice->status, ['pending','paid'], true)) return false;
        return $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin');
    }
    public function update(User $user, Invoice $invoice){
        // Editar solo cuando está en borrador
        if($invoice->status !== 'draft') return false;
        // Admins del condominio/torre y el creador pueden editar
        if($user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin')) return true;
        return $invoice->created_by === $user->id;
    }
    public function reissue(User $user, Invoice $invoice){
        // Reemisión segura: solo admins sobre facturas aprobadas/pagadas no anuladas.
        if(!in_array((string)$invoice->status, ['pending','paid'], true)) return false;
        if($invoice->voided_at !== null) return false;
        return $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin');
    }
    public function view(User $user, Invoice $invoice){
        if($user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin')) return true;
        // Owners/residents: solo pueden ver sub-facturas de su propio apartamento
        if(!$invoice->apartment_id) return false; // bloquear facturas padre
        $apartmentIds = $this->userApartmentIds($user);
        return $apartmentIds->contains($invoice->apartment_id);
    }
}
