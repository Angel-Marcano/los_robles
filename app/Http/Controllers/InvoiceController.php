<?php
namespace App\Http\Controllers; 
use App\Models\{Invoice,ExpenseItem,CurrencyRate,Tower,Apartment,PaymentReport}; 
use Dompdf\Dompdf; 
use Dompdf\Options; 
use Illuminate\Http\Request; 
use App\Services\BillingService;
use Illuminate\Support\Facades\File;

class InvoiceController extends Controller {
    public function index(Request $request){
        $this->authorize('viewAny', Invoice::class);
        $user = auth()->user();
        $isAdmin = $user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin');
        $q = Invoice::query()
            ->with(['tower','apartment.tower','children.apartment.tower'])
            ->withCount('children');

        // Owners/residents: only see their own sub-invoices (child invoices)
        if(!$isAdmin){
            $ownedAptIds = \App\Models\Ownership::where('user_id',$user->id)->pluck('apartment_id');
            $q->whereNotNull('parent_id')->whereIn('apartment_id', $ownedAptIds);
        }
        if($request->filled('period')){ $q->where('period',$request->get('period')); }
        if($request->filled('status')){ $q->where('status',$request->get('status')); }
        // Admin-only filters
        if($isAdmin){
            if($request->filled('tower_id')){ $q->where('tower_id',$request->get('tower_id')); }
            // Por defecto: mostrar solo facturas padre (no listar hijas en el listado principal)
            $type = $request->get('type', 'parent');
            if($type === 'all'){
                // sin filtro
            } elseif($type === 'child') {
                $q->whereNotNull('parent_id');
            } else {
                // parent (default)
                $q->whereNull('parent_id');
            }

            // Compatibilidad hacia atrás: si alguien usa filtros antiguos
            if($request->filled('type')){
                $legacy = $request->get('type');
                if($legacy==='simple'){
                    $q->whereNull('parent_id')->whereDoesntHave('children');
                }
            }
            if($request->filled('created_from')){ $q->whereDate('created_at','>=',$request->get('created_from')); }
            if($request->filled('created_to')){ $q->whereDate('created_at','<=',$request->get('created_to')); }
        }
        $perPage=(int)$request->get('per_page',20); if(!in_array($perPage,[10,20,50])){ $perPage=20; }
        $invoices=$q->orderByDesc('id')->paginate($perPage)->appends($request->query());
        $towers = $isAdmin ? Tower::orderBy('name')->get() : collect();
        return view('invoices.index', compact('invoices','towers','isAdmin'));
    }
    public function pdf(Invoice $invoice){ 
        $this->authorize('view',$invoice); 
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);
        // Eager load to avoid lazy-loading issues and N+1 in view
        $invoice->load(['items.apartment','items.expenseItem','tower','apartment','paymentReports']);
        // Filter items for residents: show only their apartment charges
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin') || $invoice->created_by === $user->id);
        $items = $invoice->items;
        if(!$isAdmin){
            $ownedAptIds = \App\Models\Ownership::where('user_id',$user->id)->pluck('apartment_id');
            $items = $items->whereIn('apartment_id',$ownedAptIds);
        }
        // Compute personalized totals for viewer
        $myTotalUsd = round($items->sum('subtotal_usd'),2);
        $myTotalVes = round($items->sum('subtotal_ves'),2);
        $html=view('invoices.pdf',[
            'invoice'=>$invoice,
            'items'=>$items,
            'viewerTotals'=>[ 'usd'=>$myTotalUsd, 'ves'=>$myTotalVes, 'isAdmin'=>$isAdmin ],
        ])->render(); 
        $dompdfTempDir = storage_path('app/dompdf/temp');
        $dompdfFontCacheDir = storage_path('app/dompdf/font-cache');
        if(!File::exists($dompdfTempDir)){ File::makeDirectory($dompdfTempDir, 0775, true); }
        if(!File::exists($dompdfFontCacheDir)){ File::makeDirectory($dompdfFontCacheDir, 0775, true); }
        $options = new Options();
        $options->set('defaultFont','DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false); // inline CSS only
        $options->set('isFontSubsettingEnabled', true);
        $options->set('dpi', 96);
        $options->set('tempDir', $dompdfTempDir);
        $options->set('fontCache', $dompdfFontCacheDir);
        $options->set('chroot', base_path());
        try {
            $dompdf=new Dompdf($options); 
            $dompdf->loadHtml($html);
            $dompdf->setPaper('letter','portrait');
            $dompdf->render(); 
            return response($dompdf->output(),200,['Content-Type'=>'application/pdf']); 
        } catch (\Throwable $e) {
            Log::error('PDF generation failed', [
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['pdf' => 'No se pudo generar el PDF. Revisa permisos de storage y extensiones PHP del servidor.']);
        }
    }
    public function markPaid(Invoice $invoice, Request $request){ 
        $this->authorize('markPaid',$invoice); 

        // Evitar inconsistencia: no permitir marcar como pagada si hay abonos reportados pendientes de revisión.
        $invoiceIdsToCheck = [$invoice->id];
        if(!$invoice->parent_id && $invoice->children()->exists() && $request->boolean('cascade')){
            $invoiceIdsToCheck = array_merge($invoiceIdsToCheck, $invoice->children()->pluck('id')->all());
        }
        $hasReportedPayments = PaymentReport::query()
            ->whereIn('invoice_id', $invoiceIdsToCheck)
            ->where('status', 'reported')
            ->exists();
        if($hasReportedPayments){
            return back()->withErrors(['invoice' => 'Hay abonos reportados pendientes de aprobación/rechazo. Primero revísalos (Aprobar/Rechazar) antes de marcar como pagada.']);
        }

        // Restricción: si es factura padre con hijas pendientes y NO se pidió cascada, bloquear
        if(!$invoice->parent_id && $invoice->children()->exists()){
            $hasPendingChildren = $invoice->children()->where('status','!=','paid')->exists();
            if($hasPendingChildren && !$request->boolean('cascade')){
                return back()->withErrors(['invoice'=>'No puedes marcar la factura padre como pagada mientras existan sub-facturas pendientes. Usa la opción "Marcar pagada (padre e hijas)".']);
            }
        }
        if($invoice->status!=='paid'){ 
            $rate=CurrencyRate::where('active',true)->orderByDesc('valid_from')->first(); 
            $lateUsd=$invoice->computeLateFeeUsd(); 
            $lateVes=round($lateUsd * ($rate ? $rate->rate : $invoice->exchange_rate_used),2); 
            // Si es padre con hijas y se solicita cascada, marcar hijas y luego el padre
            if(!$invoice->parent_id && $invoice->children()->exists() && $request->boolean('cascade')){
                foreach($invoice->children as $child){
                    if($child->status !== 'paid'){
                        $childLateUsd = $child->computeLateFeeUsd();
                        $childLateVes = round($childLateUsd * ($rate ? $rate->rate : $child->exchange_rate_used),2);
                        $child->update([
                            'status'=>'paid','paid_at'=>now(),'paid_exchange_rate'=>$rate ? $rate->rate : $child->exchange_rate_used,
                            'late_fee_accrued_usd'=>$childLateUsd,'late_fee_accrued_ves'=>$childLateVes
                        ]);
                    }
                }
                // Tras marcar hijas, marcar el padre
                $invoice->update([
                    'status'=>'paid','paid_at'=>now(),'paid_exchange_rate'=>$rate ? $rate->rate : $invoice->exchange_rate_used,
                    'late_fee_accrued_usd'=>$lateUsd,'late_fee_accrued_ves'=>$lateVes
                ]);
            } else {
                // Flujo normal: marcar solo la factura actual
                $invoice->update([
                    'status'=>'paid','paid_at'=>now(),'paid_exchange_rate'=>$rate ? $rate->rate : $invoice->exchange_rate_used,
                    'late_fee_accrued_usd'=>$lateUsd,'late_fee_accrued_ves'=>$lateVes
                ]); 
                // Si es una sub-factura, verificar si todas las hermanas están pagadas para marcar al padre
                if($invoice->parent_id){
                    $parent = Invoice::find($invoice->parent_id);
                    if($parent && $parent->children()->where('status','!=','paid')->count()===0){
                        $parentLateUsd = $parent->computeLateFeeUsd();
                        $parentLateVes = round($parentLateUsd * ($rate ? $rate->rate : $parent->exchange_rate_used),2);
                        $parent->update([
                            'status'=>'paid','paid_at'=>now(),'paid_exchange_rate'=>$rate ? $rate->rate : $parent->exchange_rate_used,
                            'late_fee_accrued_usd'=>$parentLateUsd,'late_fee_accrued_ves'=>$parentLateVes
                        ]);
                    }
                }
            }
        } 
        return redirect()->route('invoices.show',$invoice); 
    }
    public function approve(Invoice $invoice){
        $this->authorize('update',$invoice);
        if($invoice->status!=='draft') { return redirect()->route('invoices.show',$invoice)->with('status','La factura no está en borrador'); }
        // Debe tener items
        if($invoice->items()->count()===0){ return back()->withErrors(['invoice'=>'La factura no tiene ítems']); }

        $childInvoices = \DB::transaction(function () use ($invoice) {
            // Generar sub-facturas por apartamento
            $invoice->load(['items']);
            $itemsByApt = $invoice->items->groupBy('apartment_id');

            // Asegurar que la tasa usada sea la misma para exchange_rate_used y total_ves
            $usedRate = (float) ($invoice->exchange_rate_used ?? 0);
            if($usedRate <= 0){
                $rate = CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();
                $usedRate = (float) ($rate ? $rate->rate : 0);
                $invoice->update(['exchange_rate_used' => $usedRate]);
            }

            // Preload apartment info for numbering + tower assignment
            $aptRows = \App\Models\Apartment::whereIn('id', $itemsByApt->keys())->get(['id','code','tower_id'])->keyBy('id');

            // Preload active owners for snapshot
            $ownersByApt = \App\Models\Ownership::whereIn('apartment_id', $itemsByApt->keys())
                ->where('active', true)
                ->where('role', 'owner')
                ->with('user:id,name,email,document_type,document_number')
                ->get()
                ->keyBy('apartment_id');

            $childInvoices = collect();
            foreach($itemsByApt as $apartmentId=>$rows){
                $totalUsd = round($rows->sum('subtotal_usd'),2);
                $aptCode = optional($aptRows->get($apartmentId))->code ?? ('APT-'.$apartmentId);
                $childTowerId = $invoice->tower_id ?? optional($aptRows->get($apartmentId))->tower_id;

                // Snapshot del propietario activo
                $ownerUser = optional(optional($ownersByApt->get($apartmentId))->user);
                $ownerName = $ownerUser->name;
                $ownerEmail = $ownerUser->email;
                $docType = $ownerUser->document_type;
                $docNum  = $ownerUser->document_number;
                $ownerDocument = ($docType && $docNum) ? ($docType . '-' . $docNum) : $docNum;

                // Generar número base y asegurar unicidad con sufijo incremental si existe
                $baseNumber = 'INV-'.$invoice->period.'-'.$aptCode;
                $number = $baseNumber;
                $suffix = 2;
                while(\App\Models\Invoice::where('number',$number)->exists()){
                    $number = $baseNumber.'-'.$suffix;
                    $suffix++;
                    if($suffix > 50){ break; } // límite de seguridad
                }
                $child = \App\Models\Invoice::create([
                    'number'           => $number,
                    'parent_id'         => $invoice->id,
                    'apartment_id'      => $apartmentId,
                    'tower_id'          => $childTowerId,
                    'created_by'        => $invoice->created_by,
                    'period'            => $invoice->period,
                    'due_date'          => $invoice->due_date,
                    'status'            => 'pending',
                    'late_fee_type'     => $invoice->late_fee_type,
                    'late_fee_scope'    => $invoice->late_fee_scope,
                    'late_fee_value'    => $invoice->late_fee_value,
                    'exchange_rate_used'=> $usedRate,
                    'total_usd'         => $totalUsd,
                    'total_ves'         => round($totalUsd * $usedRate, 2),
                    'owner_name'        => $ownerName,
                    'owner_email'       => $ownerEmail,
                    'owner_document'    => $ownerDocument,
                ]);
                // Copiar items de ese apartamento a la sub-factura
                foreach($rows as $row){
                    \App\Models\InvoiceItem::create([
                        'invoice_id'      => $child->id,
                        'apartment_id'    => $row->apartment_id,
                        'expense_item_id' => $row->expense_item_id,
                        'base_amount_usd' => $row->base_amount_usd,
                        'quantity'        => $row->quantity,
                        'distributed'     => $row->distributed,
                        'subtotal_usd'    => $row->subtotal_usd,
                        'subtotal_ves'    => $row->subtotal_ves,
                    ]);
                }
                $childInvoices->push($child);
            }
            // Marcar padre como pending
            $invoice->update(['status'=>'pending']);
            return $childInvoices;
        });

        // Notificar propietarios con sus sub-facturas (fuera de transacción)
        $byApt = $childInvoices->keyBy('apartment_id');
        $recipientPairs = \App\Models\Ownership::whereIn('apartment_id',$byApt->keys())->get(['user_id','apartment_id']);
        foreach($recipientPairs as $pair){
            $user = \App\Models\User::find($pair->user_id);
            $child = $byApt->get($pair->apartment_id);
            if($user && $user->email && $child){ \Mail::to($user->email)->queue(new \App\Mail\InvoiceCreatedMail($child)); }
        }
        return redirect()->route('invoices.show',$invoice)->with('status','Factura aprobada y sub-facturas generadas');
    }
    public function show(Invoice $invoice){ 
        $this->authorize('view',$invoice); 
        // Eager load relations for display
		$invoice->load(['items.apartment','items.expenseItem','tower','children.apartment','children.paymentReports','apartment','paymentReports']);
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('super_admin') || $user->hasRole('condo_admin') || $user->hasRole('tower_admin') || $invoice->created_by === $user->id);
        $items = $invoice->items;
        if(!$isAdmin){
            $ownedAptIds = \App\Models\Ownership::where('user_id',$user->id)->pluck('apartment_id');
            $items = $items->whereIn('apartment_id',$ownedAptIds);
        }
            $isParent = !$invoice->parent_id && ($invoice->children && $invoice->children->count()>0);
            $allChildrenPaid = $isParent ? ($invoice->children->where('status','paid')->count() === $invoice->children->count()) : false;
            return view('invoices.show',['invoice'=>$invoice,'items'=>$items,'isAdmin'=>$isAdmin,'isParent'=>$isParent,'allChildrenPaid'=>$allChildrenPaid]); 
    }
    public function create(Request $r){
        $this->authorize('create',Invoice::class);
        $towers = Tower::orderBy('name')->get();
        $activeRate = CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();
        $selectedTower = null;
        if($r->filled('tower_id')){ $selectedTower = Tower::find($r->tower_id); }
        // Apartamentos según torre seleccionada (o todos)
        $apartmentsQuery = Apartment::query();
        if($selectedTower){ $apartmentsQuery->where('tower_id',$selectedTower->id); }
        $apartments = $apartmentsQuery->orderBy('code')->get();
        $items = ExpenseItem::where('active',true)->orderBy('name')->get();
        return view('invoices.create',compact('selectedTower','towers','apartments','items','activeRate')); 
    }
    public function edit(Invoice $invoice, Request $r){
        $this->authorize('update',$invoice);
        if($invoice->status!=='draft'){ return redirect()->route('invoices.show',$invoice)->with('status','Solo se puede editar en borrador'); }
        $towers = Tower::orderBy('name')->get();
        $selectedTower = $invoice->tower_id ? Tower::find($invoice->tower_id) : null;
        $activeRate = CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();
        $apartmentsQuery = Apartment::query();
        if($selectedTower){ $apartmentsQuery->where('tower_id',$selectedTower->id); }
        $apartments = $apartmentsQuery->orderBy('code')->get();
        $items = ExpenseItem::where('active',true)->orderBy('name')->get();
        // Prefill: aggregate current invoice items by expense_item_id
        $existing = $invoice->items()->with('expenseItem')->get(['expense_item_id','apartment_id','subtotal_usd','distributed','quantity']);
        $selectedApartmentIds = $existing->pluck('apartment_id')->unique()->values();
        $grouped = [];
        foreach($existing as $row){
            $eid = (int)$row->expense_item_id;
            if(!isset($grouped[$eid])){
                $grouped[$eid] = [
                    'expense_item_id'=>$eid,
                    'name'=> optional($row->expenseItem)->name ?? ('Item '.$eid),
                    'type'=> optional($row->expenseItem)->type ?? '-',
                    'amount'=>0.0,
                    'quantity'=> (int) ($row->quantity ?? 1),
                    'distribution'=> $row->distributed ? 'aliquota' : 'equal',
                    'apartment_ids'=> [],
                ];
            }
            $grouped[$eid]['amount'] += (float)$row->subtotal_usd; // sum subtotals
            $grouped[$eid]['apartment_ids'][] = (int) $row->apartment_id;
            // ensure quantity is consistent (first non-null wins)
            if(empty($grouped[$eid]['quantity'])){ $grouped[$eid]['quantity'] = (int) ($row->quantity ?? 1); }
        }
        // Convert summed subtotal to original amount by dividing by quantity when possible
        $prefill = [];
        foreach($grouped as $g){
            $qty = max(1, (int) ($g['quantity'] ?? 1));
            $aptCount = max(1, count($g['apartment_ids'] ?? []));
            $amount = $g['amount'];
            $divisor = (($g['distribution'] ?? 'aliquota') === 'equal') ? ($qty * $aptCount) : $qty;
            $perUnit = $divisor > 0 ? round(((float)$amount) / $divisor, 2) : (float)$amount;
            $g['amount'] = $perUnit;
            $g['apartment_ids'] = collect($g['apartment_ids'] ?? [])->filter()->unique()->values()->all();
            $prefill[] = $g;
        }
        return view('invoices.edit',compact('invoice','selectedTower','towers','apartments','items','prefill','selectedApartmentIds','activeRate'));
    }
    public function store(Request $r, BillingService $billing){
        $this->authorize('store',Invoice::class);
        $data=$r->validate([
            'tower_id'        =>'nullable|exists:tenant.towers,id',
            'period'          =>'required|date_format:Y-m',
            'apartment_ids'   =>'nullable|array',
            'items_payload'   =>'nullable|string',
            'late_fee_type'   =>'nullable|in:percent,fixed',
            'late_fee_scope'  =>'nullable|in:day,week,month',
            'late_fee_value'  =>'nullable|numeric|min:0'
        ]);
        $rawPayload = $data['items_payload'] ?? '[]';
        $items = json_decode($rawPayload, true);
        if(!is_array($items)){
            $items = [];
        }
        if(count($items) > 0){
            if(empty($data['apartment_ids']) || !is_array($data['apartment_ids'])){
                return back()->withErrors(['apartment_ids'=>'Debes seleccionar al menos un apartamento'])->withInput();
            }
            foreach($items as $i){
                if(($i['amount'] ?? 0) < 0){ return back()->withErrors(['items_payload'=>'Monto negativo no permitido'])->withInput(); }
                if(!in_array(($i['distribution'] ?? 'aliquota'), ['aliquota','equal'])){ return back()->withErrors(['items_payload'=>'Distribución inválida'])->withInput(); }
            }
        }
        // Adaptar al BillingService: extraer ids y pasar detalles por separado
        $expenseItemIds = array_map(fn($i)=>$i['expense_item_id'], $items);
        $invoice=$billing->generateInvoice(
            $data['period'],
            $expenseItemIds,
            $data['apartment_ids'] ?? [],
            ['type'=>$data['late_fee_type']??null,'scope'=>$data['late_fee_scope']??null,'value'=>$data['late_fee_value']??null],
            $data['tower_id']??null,
            $items // pasar detalles por ítem (amount, quantity, distribution)
        );
        return redirect()->route('invoices.show',$invoice); 
    }
    public function update(Invoice $invoice, Request $r, BillingService $billing){
        $this->authorize('update',$invoice);
        if($invoice->status!=='draft'){ return redirect()->route('invoices.show',$invoice)->with('status','Solo se puede editar en borrador'); }
        $data=$r->validate([
            'tower_id'        =>'nullable|exists:tenant.towers,id',
            'period'          =>'required|date_format:Y-m',
            'apartment_ids'   =>'nullable|array',
            'items_payload'   =>'nullable|string',
            'late_fee_type'   =>'nullable|in:percent,fixed',
            'late_fee_scope'  =>'nullable|in:day,week,month',
            'late_fee_value'  =>'nullable|numeric|min:0'
        ]);
        $rawPayload = $data['items_payload'] ?? '[]';
        $items = json_decode($rawPayload, true);
        if(!is_array($items)){
            $items = [];
        }
        if(count($items) > 0){
            if(empty($data['apartment_ids']) || !is_array($data['apartment_ids'])){
                return back()->withErrors(['apartment_ids'=>'Debes seleccionar al menos un apartamento'])->withInput();
            }
            foreach($items as $i){
                if(($i['amount'] ?? 0) < 0){ return back()->withErrors(['items_payload'=>'Monto negativo no permitido'])->withInput(); }
                if(!in_array(($i['distribution'] ?? 'aliquota'), ['aliquota','equal'])){ return back()->withErrors(['items_payload'=>'Distribución inválida'])->withInput(); }
            }
        }
        // Rebuild invoice items: delete current and re-generate with service (keeping same invoice record)
        \DB::transaction(function() use ($invoice){ $invoice->items()->delete(); });
        $expenseItemIds = array_map(fn($i)=>$i['expense_item_id'], $items);

        // Si no hay ítems, permitir guardar borrador vacío
        if(count($items) === 0){
            $rate = \App\Models\CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();
            $invoice->update([
                'tower_id'          => $data['tower_id'] ?? $invoice->tower_id,
                'period'            => $data['period'],
                'late_fee_type'     => $data['late_fee_type'] ?? null,
                'late_fee_scope'    => $data['late_fee_scope'] ?? null,
                'late_fee_value'    => $data['late_fee_value'] ?? null,
                'exchange_rate_used'=> $rate ? $rate->rate : $invoice->exchange_rate_used,
                'total_usd'         => 0,
                'total_ves'         => 0,
            ]);
            return redirect()->route('invoices.show',$invoice)->with('status','Factura actualizada');
        }

        // Temporarily compute using service by creating a new invoice object would create another record.
        // Instead, we simulate generation and update totals for existing invoice.
        $rate = \App\Models\CurrencyRate::where('active',true)->orderByDesc('valid_from')->first();
        $apartments   = \App\Models\Apartment::whereIn('id',$data['apartment_ids'])->get();
        $expenseItems = \App\Models\ExpenseItem::whereIn('id',$expenseItemIds)->get();
        $totalUsd     = 0;
        $detailsById = [];
        foreach($items as $d){ $detailsById[(int)$d['expense_item_id']] = $d; }
        foreach($expenseItems as $item){
            $detail = $detailsById[$item->id] ?? ['amount'=>0,'quantity'=>1,'distribution'=>'aliquota'];
            $totalAmount = (float) ($detail['amount'] ?? 0);
            $quantity    = max(1, (int) ($detail['quantity'] ?? 1));
            $distribution= $detail['distribution'] ?? 'aliquota';
            // Per-item apartments override
            $itemApartmentIds = collect($detail['apartment_ids'] ?? [])->filter()->map(fn($v)=>(int)$v)->values();
            $apartmentsForItem = $itemApartmentIds->isNotEmpty() ? \App\Models\Apartment::whereIn('id',$itemApartmentIds)->get() : $apartments;
            if($distribution === 'aliquota'){
                $sumAliquot = $apartmentsForItem->sum('aliquot_percent');
                foreach($apartmentsForItem as $ap){
                    $portion = $sumAliquot > 0 ? round($totalAmount * ($ap->aliquot_percent / $sumAliquot),2) : 0;
                    $subtotal = $portion * $quantity;
                    $totalUsd += $subtotal;
                    \App\Models\InvoiceItem::create([
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
            } else {
                $portionEach = round($totalAmount * $quantity, 2);
                foreach($apartmentsForItem as $ap){
                    $totalUsd += $portionEach;
                    \App\Models\InvoiceItem::create([
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
            'tower_id'          => $data['tower_id'] ?? $invoice->tower_id,
            'period'            => $data['period'],
            'late_fee_type'     => $data['late_fee_type'] ?? null,
            'late_fee_scope'    => $data['late_fee_scope'] ?? null,
            'late_fee_value'    => $data['late_fee_value'] ?? null,
            'exchange_rate_used'=> $rate->rate ?? $invoice->exchange_rate_used,
            'total_usd'         => $totalUsd,
            'total_ves'         => $totalUsd * ($rate->rate ?? 0),
        ]);
        return redirect()->route('invoices.show',$invoice)->with('status','Factura actualizada');
    }
}

