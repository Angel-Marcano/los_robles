<?php
namespace App\Http\Requests;

class UpdateInvoiceRequest extends BaseInvoiceRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');
        return $this->user() !== null && $invoice && $this->user()->can('update', $invoice);
    }
}
