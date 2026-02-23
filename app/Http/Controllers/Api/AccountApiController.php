<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountApiController extends Controller {
    use ApiResponses;
    public function index(Request $r){
        $user=$r->user();
        if(!method_exists($user,'hasAnyRole') || !$user->hasAnyRole(['super_admin','condo_admin','tower_admin'])){
            return $this->errorResponse('FORBIDDEN', __('errors.FORBIDDEN'), 403);
        }
        $q=Account::query();
        // Alcance según rol (tenant: ya aislado por BD). Tower admin restringe a su torre y sus apartamentos.
        if(method_exists($user,'hasRole') && $user->hasRole('tower_admin') && isset($user->tower_id)){
            $q->where(function($sub) use ($user){
                $sub->where(function($w) use ($user){ $w->where('owner_type','Tower')->where('owner_id',$user->tower_id); })
                    ->orWhere(function($w) use ($user){ $w->where('owner_type','Apartment')->whereIn('owner_id',function($q2) use ($user){ $q2->select('id')->from('apartments')->where('tower_id',$user->tower_id); }); });
            });
        }
        if($r->filled('owner_type')){ $q->where('owner_type',$r->get('owner_type')); }
        if($r->filled('owner_id')){ $q->where('owner_id',$r->get('owner_id')); }
        if($r->get('export')==='csv'){ return $this->exportCsvStream($q); }
        $perPage=(int)$r->get('per_page',20); if(!in_array($perPage,[10,20,50])){ $perPage=20; }
        $paginator=$q->orderBy('id')->paginate($perPage)->appends($r->query());
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
                ]
            ]
        ]);
    }
    protected function exportCsvStream($q){
        $filename='accounts_'.now()->format('Ymd_His').'.csv';
        $callback=function() use ($q){
            $handle=fopen('php://output','w');
            fputcsv($handle,['ID','Nombre','Owner_Type','Owner_ID','Balance_USD','Balance_VES']);
            $q->orderBy('id')->chunk(500,function($chunk) use ($handle){
                foreach($chunk as $acc){
                    fputcsv($handle,[$acc->id,$acc->name,$acc->owner_type,$acc->owner_id,$acc->balance_usd,$acc->balance_ves]);
                }
            });
            fclose($handle);
        };
        return response()->streamDownload($callback,$filename,['Content-Type'=>'text/csv']);
    }
}
