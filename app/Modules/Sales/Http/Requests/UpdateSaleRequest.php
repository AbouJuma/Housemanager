<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('sale'));
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['sometimes', 'exists:units,id'],
            'new_buyer_id' => ['nullable', 'exists:buyers,id'],
            'sale_date' => ['sometimes', 'date'],
            'total_price' => ['sometimes', 'numeric', 'min:0'],
            'down_payment' => ['sometimes', 'numeric', 'min:0'],
            'installment_period' => ['sometimes', 'integer', 'min:1', 'max:360'],
            'installment_amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['pending', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
