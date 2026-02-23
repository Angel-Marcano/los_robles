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
     */
    public function generateInvoice(string $period,array $expenseItemIds,array $apartmentIds,array $lateFee=[],?int $towerId=null, array $itemDetails=[]): Invoice {
    $rate = CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();
    return DB::transaction(function() use ($period,$expenseItemIds,$apartmentIds,$lateFee,$rate,$towerId,$itemDetails){
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
                'exchange_rate_used'=> $rate->rate ?? 0,
                'total_usd'         => 0,
                'total_ves'         => 0,
            ]);

            $apartments   = Apartment::whereIn('id',$apartmentIds)->get();
            $expenseItems = ExpenseItem::whereIn('id',$expenseItemIds)->get();
            $totalUsd     = 0;

            // Map details by expense_item_id for quick lookup
            $detailsById = [];
            foreach($itemDetails as $d){ $detailsById[(int)$d['expense_item_id']] = $d; }
            foreach($expenseItems as $item){
                $detail = $detailsById[$item->id] ?? ['amount'=>0,'quantity'=>1,'distribution'=>'aliquota'];
                $totalAmount = (float) ($detail['amount'] ?? 0);
                $quantity    = max(1, (int) ($detail['quantity'] ?? 1));
                $distribution= $detail['distribution'] ?? 'aliquota';

                // Allow per-item apartment selection overriding global list
                $itemApartmentIds = collect($detail['apartment_ids'] ?? [])->filter()->map(fn($v)=>(int)$v)->values();
                $apartmentsForItem = $itemApartmentIds->isNotEmpty() ? Apartment::whereIn('id',$itemApartmentIds)->get() : $apartments;

                if($distribution === 'aliquota'){
                    $sumAliquot = $apartmentsForItem->sum('aliquot_percent');
                    foreach($apartmentsForItem as $ap){
                        $portion = $sumAliquot > 0 ? round($totalAmount * ($ap->aliquot_percent / $sumAliquot),2) : 0;
                        $subtotal = $portion * $quantity;
                        $totalUsd += $subtotal;
                        InvoiceItem::create([
                            'invoice_id'      => $invoice->id,
                            'apartment_id'    => $ap->id,
                            'expense_item_id' => $item->id,
                            'base_amount_usd' => $totalAmount,
                            'quantity'        => $quantity,
                            'distributed'     => true,
                            'subtotal_usd'    => $subtotal,
                            'subtotal_ves'    => $subtotal * ($rate->rate ?? 0),
                        ]);
                    }
                } else { // equal distribution
                    $count = max(1, $apartmentsForItem->count());
                    $portionEach = $count > 0 ? round(($totalAmount * $quantity) / $count, 2) : 0;
                    foreach($apartmentsForItem as $ap){
                        $totalUsd += $portionEach;
                        InvoiceItem::create([
                            'invoice_id'      => $invoice->id,
                            'apartment_id'    => $ap->id,
                            'expense_item_id' => $item->id,
                            'base_amount_usd' => $totalAmount,
                            'quantity'        => $quantity,
                            'distributed'     => false,
                            'subtotal_usd'    => $portionEach,
                            'subtotal_ves'    => $portionEach * ($rate->rate ?? 0),
                        ]);
                    }
                }
            }

            $invoice->update([
                'total_usd' => $totalUsd,
                'total_ves' => $totalUsd * ($rate->rate ?? 0),
            ]);

            // Notificar por correo se hará al aprobar (no en borrador)
            return $invoice;
        });
    }
}
