<?php
namespace App\Http\Controllers\Api\Concerns;
trait ApiResponses {
    protected function errorResponse(string $code,string $message,int $status=400,array $extra=[]){
        return response()->json(array_merge(['error'=>['code'=>$code,'message'=>$message]],$extra),$status);
    }
}
