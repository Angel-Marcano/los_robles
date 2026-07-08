<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tower_id'        => 'nullable|exists:tenant.towers,id',
            'period'          => 'required|date_format:Y-m',
            'apartment_ids'   => 'nullable|array',
            'items_payload'   => 'nullable|string',
            'late_fee_type'   => 'nullable|in:percent,fixed',
            'late_fee_scope'  => 'nullable|in:day,week,month',
            'late_fee_value'  => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $items = $this->items();
            if (count($items) === 0) {
                return;
            }
            $apartmentIds = $this->input('apartment_ids');
            if (empty($apartmentIds) || !is_array($apartmentIds)) {
                $v->errors()->add('apartment_ids', 'Debes seleccionar al menos un apartamento');
            }
            foreach ($items as $i) {
                if (($i['amount'] ?? 0) < 0) {
                    $v->errors()->add('items_payload', 'Monto negativo no permitido');
                    break;
                }
                if (!in_array(($i['distribution'] ?? 'aliquota'), ['aliquota', 'equal'])) {
                    $v->errors()->add('items_payload', 'Distribución inválida');
                    break;
                }
            }
        });
    }

    /** Ítems del payload JSON ya decodificados y saneados. */
    public function items(): array
    {
        $items = json_decode($this->input('items_payload', '[]'), true);
        return is_array($items) ? $items : [];
    }
}
