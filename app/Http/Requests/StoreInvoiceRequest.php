<?php
namespace App\Http\Requests;

use App\Models\Invoice;

class StoreInvoiceRequest extends BaseInvoiceRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('store', Invoice::class);
    }
}
