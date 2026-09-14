<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Sale::class);
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'exists:units,id', function ($attribute, $value, $fail) {
                $unit = \App\Modules\Properties\Models\Unit::find($value);
                if ($unit && $unit->activeSale()) {
                    $fail('The selected unit is not available for sale. It already has an active sale.');
                }
            }],
            'new_buyer_id' => ['nullable', 'exists:buyers,id'],
            'sale_date' => ['required', 'date'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'down_payment' => ['required', 'numeric', 'min:0', 'lte:total_price'],
            'installment_period' => ['required', 'integer', 'min:1', 'max:360'],
            'installment_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
