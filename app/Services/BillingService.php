<?php
namespace App\Services;
use App\Models\{Invoice,InvoiceItem,ExpenseItem,Apartment,CurrencyRate,Ownership,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceCreatedMail;

class BillingService {
    /**
     * Genera una factura en la BD tenant (multi-tenancy por conexión).
     *
     * Parámetros:
     *  - period: string YYYY-MM
     *  - expenseItemIds: array<int>
     *  - apartmentIds: array<int>
     *  - lateFee: array{type?:string,scope?:string,value?:float}
     *  - towerId: ?int
     *  - itemDetails: array (amount, quantity, distribution, amount_ves, apartment_ids)
     *  - reserveOpts: array{include_tower?:bool, include_general?:bool}
     */
    public function generateInvoice(string $period,array $expenseItemIds,array $apartmentIds,array $lateFee=[],?int $towerId=null, array $itemDetails=[], array $reserveOpts=[]): Invoice {
    $rate = CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();
    $rateValue = (float) ($rate->rate ?? 0);
    return DB::transaction(function() use ($period,$expenseItemIds,$apartmentIds,$lateFee,$rateValue,$towerId,$itemDetails,$reserveOpts){
            $dueDate = \Carbon\Carbon::createFromFormat('Y-m',$period)->endOfMonth();
            $invoice = Invoice::create([
                'tower_id'          => $towerId,
                'created_by'        => auth()->id(),
                'period'            => $period,
                'due_date'          => $dueDate,
                'status'            => 'draft',
                'late_fee_type'     => $lateFee['type'] ?? null,
                'late_fee_scope'    => $lateFee['scope'] ?? null,
                'late_fee_value'    => $lateFee['value'] ?? null,
                'exchange_rate_used'=> $rateValue,
                'total_usd'         => 0,
                'total_ves'         => 0,
            ]);

            $totals = $this->createItems($invoice, $expenseItemIds, $apartmentIds, $itemDetails, $rateValue, $reserveOpts);

            $invoice->update([
                'total_usd' => $totals['usd'],
                'total_ves' => $totals['ves'],
            ]);

            // Notificar por correo se hará al aprobar (no en borrador)
            return $invoice;
        });
    }

    /**
     * Reconstruye los ítems de una factura en borrador y actualiza sus totales.
     * Misma matemática que generateInvoice (única fuente de verdad).
     */
    public function regenerateInvoice(Invoice $invoice, string $period, array $expenseItemIds, array $apartmentIds, array $lateFee=[], ?int $towerId=null, array $itemDetails=[], array $reserveOpts=[]): Invoice {
        $rate = CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();
        $rateValue = (float) ($rate->rate ?? $invoice->exchange_rate_used ?? 0);
        return DB::transaction(function() use ($invoice,$period,$expenseItemIds,$apartmentIds,$lateFee,$towerId,$itemDetails,$rateValue,$reserveOpts){
            $invoice->items()->delete();
            $totals = $this->createItems($invoice, $expenseItemIds, $apartmentIds, $itemDetails, $rateValue, $reserveOpts);
            $invoice->update([
                'tower_id'          => $towerId ?? $invoice->tower_id,
                'period'            => $period,
                'due_date'          => \Carbon\Carbon::createFromFormat('Y-m',$period)->endOfMonth(),
                'late_fee_type'     => $lateFee['type'] ?? null,
                'late_fee_scope'    => $lateFee['scope'] ?? null,
                'late_fee_value'    => $lateFee['value'] ?? null,
                'exchange_rate_used'=> $rateValue,
                'total_usd'         => $totals['usd'],
                'total_ves'         => $totals['ves'],
            ]);
            return $invoice;
        });
    }

    /**
     * Crea los InvoiceItem según la distribución de cada gasto y devuelve los totales.
     *  - aliquota: el monto se reparte proporcionalmente a aliquot_percent.
     *  - equal: cada apartamento paga el monto completo x cantidad.
     *
     * El monto en VES tecleado por el usuario (amount_ves) se conserva como pool y se
     * distribuye igual que el USD, evitando el redondeo que produce recalcular VES = USD * tasa.
     *
     * @return array{usd: float, ves: float}
     */
    protected function createItems(Invoice $invoice, array $expenseItemIds, array $apartmentIds, array $itemDetails, float $rateValue, array $reserveOpts = []): array {
        $includeTower   = $reserveOpts['include_tower']   ?? true;
        $includeGeneral = $reserveOpts['include_general'] ?? true;

        $apartments   = Apartment::whereIn('id',$apartmentIds)->get();
        $expenseItems = ExpenseItem::whereIn('id',$expenseItemIds)->get();
        $totalUsd     = 0;
        $totalVes     = 0;
        // Acumulado por apartamento (base para el fondo de reserva de su torre).
        $aptTotals    = [];

        // Map details by expense_item_id for quick lookup
        $detailsById = [];
        foreach($itemDetails as $d){ $detailsById[(int)$d['expense_item_id']] = $d; }
        foreach($expenseItems as $item){
            $detail = $detailsById[$item->id] ?? ['amount'=>0,'quantity'=>1,'distribution'=>'aliquota'];
            $totalAmount = (float) ($detail['amount'] ?? 0);
            $quantity    = max(1, (int) ($detail['quantity'] ?? 1));
            $distribution= $detail['distribution'] ?? 'aliquota';

            // Pool en VES tecleado por el usuario; si no viene, se deriva de USD * tasa.
            $hasVes        = array_key_exists('amount_ves', $detail) && (float) $detail['amount_ves'] > 0;
            $totalAmountVes = $hasVes ? (float) $detail['amount_ves'] : round($totalAmount * $rateValue, 2);

            // Allow per-item apartment selection overriding global list
            $itemApartmentIds = collect($detail['apartment_ids'] ?? [])->filter()->map(fn($v)=>(int)$v)->values();
            $apartmentsForItem = $itemApartmentIds->isNotEmpty() ? Apartment::whereIn('id',$itemApartmentIds)->get() : $apartments;

            if($distribution === 'aliquota'){
                $sumAliquot = $apartmentsForItem->sum('aliquot_percent');
                foreach($apartmentsForItem as $ap){
                    $fraction    = $sumAliquot > 0 ? ($ap->aliquot_percent / $sumAliquot) : 0;
                    $portionUsd  = round($totalAmount * $fraction, 2);
                    $portionVes  = round($totalAmountVes * $fraction, 2);
                    $subtotalUsd = $portionUsd * $quantity;
                    $subtotalVes = round($portionVes * $quantity, 2);
                    $totalUsd += $subtotalUsd;
                    $totalVes += $subtotalVes;
                    $aptTotals[$ap->id]['usd'] = ($aptTotals[$ap->id]['usd'] ?? 0) + $subtotalUsd;
                    $aptTotals[$ap->id]['ves'] = ($aptTotals[$ap->id]['ves'] ?? 0) + $subtotalVes;
                    $aptTotals[$ap->id]['tower_id'] = $ap->tower_id;
                    InvoiceItem::create([
                        'invoice_id'      => $invoice->id,
                        'apartment_id'    => $ap->id,
                        'expense_item_id' => $item->id,
                        'base_amount_usd' => $totalAmount,
                        'base_amount_ves' => $totalAmountVes,
                        'quantity'        => $quantity,
                        'distributed'     => true,
                        'subtotal_usd'    => $subtotalUsd,
                        'subtotal_ves'    => $subtotalVes,
                    ]);
                }
            } else { // equal distribution: cada apartamento paga el monto completo
                $perApt    = round($totalAmount * $quantity, 2);
                $perAptVes = round($totalAmountVes * $quantity, 2);
                foreach($apartmentsForItem as $ap){
                    $totalUsd += $perApt;
                    $totalVes += $perAptVes;
                    $aptTotals[$ap->id]['usd'] = ($aptTotals[$ap->id]['usd'] ?? 0) + $perApt;
                    $aptTotals[$ap->id]['ves'] = ($aptTotals[$ap->id]['ves'] ?? 0) + $perAptVes;
                    $aptTotals[$ap->id]['tower_id'] = $ap->tower_id;
                    InvoiceItem::create([
                        'invoice_id'      => $invoice->id,
                        'apartment_id'    => $ap->id,
                        'expense_item_id' => $item->id,
                        'base_amount_usd' => $totalAmount,
                        'base_amount_ves' => $totalAmountVes,
                        'quantity'        => $quantity,
                        'distributed'     => false,
                        'subtotal_usd'    => $perApt,
                        'subtotal_ves'    => $perAptVes,
                    ]);
                }
            }
        }

        // Fondo de reserva por torre: cada apartamento aporta el % de su propia torre
        // sobre el subtotal de gastos comunes de su factura. Fondos aislados por torre.
        if($includeTower && !empty($aptTotals)){
            $towerIds = collect($aptTotals)->pluck('tower_id')->filter()->unique()->values();
            $towers   = \App\Models\Tower::whereIn('id', $towerIds)->get()->keyBy('id');
            foreach($aptTotals as $aptId => $acc){
                $tower = !empty($acc['tower_id']) ? $towers->get($acc['tower_id']) : null;
                $pct   = $tower ? (float) $tower->reserve_percent : 0;
                if($pct <= 0){ continue; }
                $reserveUsd = round(($acc['usd'] ?? 0) * $pct / 100, 2);
                $reserveVes = round(($acc['ves'] ?? 0) * $pct / 100, 2);
                if($reserveUsd <= 0 && $reserveVes <= 0){ continue; }
                $totalUsd += $reserveUsd;
                $totalVes += $reserveVes;
                // Acumular la reserva de torre en el total del apto para el cálculo del general
                $aptTotals[$aptId]['usd'] = ($aptTotals[$aptId]['usd'] ?? 0) + $reserveUsd;
                $aptTotals[$aptId]['ves'] = ($aptTotals[$aptId]['ves'] ?? 0) + $reserveVes;
                InvoiceItem::create([
                    'invoice_id'      => $invoice->id,
                    'apartment_id'    => $aptId,
                    'expense_item_id' => null,
                    'base_amount_usd' => $reserveUsd,
                    'base_amount_ves' => $reserveVes,
                    'quantity'        => 1,
                    'distributed'     => false,
                    'is_reserve'      => true,
                    'reserve_type'    => 'tower',
                    'subtotal_usd'    => $reserveUsd,
                    'subtotal_ves'    => $reserveVes,
                ]);
            }
        }

        // Fondo de reserva general del condominio: % aplicado sobre el total de la
        // factura del apartamento (gastos comunes + reserva de torre si la hubo).
        if($includeGeneral && !empty($aptTotals)){
            $condo = app()->bound('currentCondominium') ? app('currentCondominium') : null;
            $generalPct = $condo ? (float) $condo->reserve_percent : 0;
            if($generalPct > 0){
                foreach($aptTotals as $aptId => $acc){
                    $reserveUsd = round(($acc['usd'] ?? 0) * $generalPct / 100, 2);
                    $reserveVes = round(($acc['ves'] ?? 0) * $generalPct / 100, 2);
                    if($reserveUsd <= 0 && $reserveVes <= 0){ continue; }
                    $totalUsd += $reserveUsd;
                    $totalVes += $reserveVes;
                    InvoiceItem::create([
                        'invoice_id'      => $invoice->id,
                        'apartment_id'    => $aptId,
                        'expense_item_id' => null,
                        'base_amount_usd' => $reserveUsd,
                        'base_amount_ves' => $reserveVes,
                        'quantity'        => 1,
                        'distributed'     => false,
                        'is_reserve'      => true,
                        'reserve_type'    => 'general',
                        'subtotal_usd'    => $reserveUsd,
                        'subtotal_ves'    => $reserveVes,
                    ]);
                }
            }
        }

        return ['usd' => round($totalUsd, 2), 'ves' => round($totalVes, 2)];
    }
}
