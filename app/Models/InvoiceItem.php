<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class InvoiceItem extends Model
{
    use HasFactory, \App\Models\Traits\UsesTenantConnection;
    protected $fillable=['invoice_id','apartment_id','expense_item_id','base_amount_usd','quantity','distributed','subtotal_usd','subtotal_ves'];
    protected $casts=['distributed'=>'boolean','quantity'=>'integer','base_amount_usd'=>'decimal:2','subtotal_usd'=>'decimal:2','subtotal_ves'=>'decimal:2'];
    public function apartment(){return $this->belongsTo(Apartment::class);}    
    public function expenseItem(){return $this->belongsTo(ExpenseItem::class);}    
}
