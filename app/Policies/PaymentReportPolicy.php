<?php
namespace App\Policies; use App\Models\{User, PaymentReport, Invoice};
class PaymentReportPolicy {
	public function create(User $user){ return $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin') || $user->hasRole('owner') || $user->hasRole('tenant'); }
	public function approve(User $user, PaymentReport $pr){
		$invoice = $pr->invoice;
		if($user->hasRole('super_admin')) return true;
		// Si es factura de torre, tower_admin puede aprobar; condo_admin no.
		if($invoice->tower_id){ return $user->hasRole('tower_admin'); }
		// Factura de condominio: condo_admin puede aprobar.
		return $user->hasRole('condo_admin');
	}
	public function reject(User $user, PaymentReport $pr){ return $this->approve($user,$pr); }
}
