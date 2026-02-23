<?php
namespace App\Http\Controllers; use Illuminate\Http\Request; use App\Models\AuditLog; use App\Models\User;
class AuditLogController extends Controller {
    public function index(Request $r){
        $this->authorizeView($r);
        [$q,$perPage,$distinctTypes,$distinctActions,$users] = $this->buildFilteredQuery($r);
        // Export CSV si se solicita
        if($r->get('export')==='csv'){
            return $this->exportCsvStream(clone $q,$r);
        }
        $logs=$q->orderByDesc('id')->paginate($perPage)->appends($r->query());
        return view('audit_logs.index',compact('logs','distinctTypes','distinctActions','users','perPage'));
    }
    protected function buildFilteredQuery(Request $r){
        $q=AuditLog::query()->with('user');
        if($r->filled('entity_type')){ $q->where('entity_type',$r->get('entity_type')); }
        if($r->filled('action')){ $q->where('action',$r->get('action')); }
        if($r->filled('user_id')){ $q->where('user_id',$r->get('user_id')); }
        if($r->filled('date_from')){ $q->whereDate('created_at','>=',$r->get('date_from')); }
        if($r->filled('date_to')){ $q->whereDate('created_at','<=',$r->get('date_to')); }
        $perPage=(int)$r->get('per_page',20); if(!in_array($perPage,[10,20,50])){ $perPage=20; }
        $distinctTypes=AuditLog::select('entity_type')->distinct()->pluck('entity_type');
        $distinctActions=AuditLog::select('action')->distinct()->pluck('action');
        $users=User::orderBy('name')->get(['id','name']);
        return [$q,$perPage,$distinctTypes,$distinctActions,$users];
    }
    protected function exportCsvStream($q,Request $r){
        $filename='audit_logs_'.now()->format('Ymd_His').'.csv';
        $callback=function() use ($q){
            $handle=fopen('php://output','w');
            fputcsv($handle,['ID','Fecha','Usuario','Entidad','Acción','Entidad_ID','IP','Cambios']);
            $q->orderByDesc('id')->chunk(500,function($chunk) use ($handle){
                foreach($chunk as $log){
                    fputcsv($handle,[
                        $log->id,
                        $log->created_at,
                        optional($log->user)->name,
                        $log->entity_type,
                        $log->action,
                        $log->entity_id,
                        $log->ip,
                        json_encode($log->changes,JSON_UNESCAPED_UNICODE)
                    ]);
                }
            });
            fclose($handle);
        };
        return response()->streamDownload($callback,$filename,['Content-Type'=>'text/csv']);
    }
    protected function authorizeView(Request $r){
        $user=$r->user(); if(!$user){ abort(403); }
        if(method_exists($user,'hasAnyRole') && !$user->hasAnyRole(['super_admin','condo_admin'])){ abort(403); }
    }
}
?>
