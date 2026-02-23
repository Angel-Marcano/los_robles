<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceApiController extends Controller {
    public function index(Request $r){
        $q=Invoice::query()->with('items');
        if($r->filled('period')){ $q->where('period',$r->get('period')); }
        if($r->filled('status')){ $q->where('status',$r->get('status')); }
        if($r->filled('tower_id')){ $q->where('tower_id',$r->get('tower_id')); }
        if($r->filled('created_from')){ $q->whereDate('created_at','>=',$r->get('created_from')); }
        if($r->filled('created_to')){ $q->whereDate('created_at','<=',$r->get('created_to')); }
        if($r->get('export')==='csv'){ return $this->exportCsvStream($q); }
        $perPage = (int) $r->get('per_page',15); $perPage = $perPage>100?100:$perPage;
        $paginator = $q->orderByDesc('id')->paginate($perPage)->appends($r->query());
        return response()->json([
            'data'=>$paginator->items(),
            'meta'=>[
                'current_page'=>$paginator->currentPage(),
                'per_page'=>$paginator->perPage(),
                'total'=>$paginator->total(),
                'last_page'=>$paginator->lastPage(),
                'ratelimit'=>[
                    'limit'=>$r->attributes->get('ratelimit_limit'),
                    'remaining'=>$r->attributes->get('ratelimit_remaining'),
                ],
            ]
        ]);
    }
    public function show(Invoice $invoice){ return response()->json(['data'=>$invoice->load('items')]); }
    protected function exportCsvStream($q){
        $filename='invoices_'.now()->format('Ymd_His').'.csv';
        $callback=function() use ($q){
            $handle=fopen('php://output','w');
            fputcsv($handle,['ID','Periodo','Estado','Total_USD','Total_VES','Mora_USD','Mora_VES','Creado','Vence']);
            $q->orderByDesc('id')->chunk(500,function($chunk) use ($handle){
                foreach($chunk as $inv){
                    fputcsv($handle,[
                        $inv->id,$inv->period,$inv->status,$inv->total_usd,$inv->total_ves,$inv->late_fee_accrued_usd,$inv->late_fee_accrued_ves,$inv->created_at,$inv->due_date
                    ]);
                }
            });
            fclose($handle);
        };
        return response()->streamDownload($callback,$filename,['Content-Type'=>'text/csv']);
    }
}
