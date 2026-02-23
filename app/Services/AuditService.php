<?php
namespace App\Services; use App\Models\AuditLog; use Illuminate\Support\Facades\Auth;
class AuditService { public function log(string $action,string $entityType,int $entityId=null,array $changes=[]):void { AuditLog::create(['user_id'=>Auth::id(),'action'=>$action,'entity_type'=>$entityType,'entity_id'=>$entityId,'changes'=>$changes,'ip'=>request()->ip(),]); } }
