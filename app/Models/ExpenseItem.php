<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ExpenseItem extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;
    // Un gasto sólo define su nombre y estado activo; el monto se especifica al facturar.
    protected $fillable=['name','type','active'];
    protected $casts=['active'=>'boolean'];
}
