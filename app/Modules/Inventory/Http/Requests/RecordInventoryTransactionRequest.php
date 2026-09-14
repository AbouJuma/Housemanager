<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordInventoryTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('inventory_item'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['receipt', 'usage', 'adjustment'])],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'direction' => ['required_if:type,adjustment', 'nullable', Rule::in(['increase', 'decrease'])],
            'reason' => ['required_if:type,adjustment', 'nullable', 'string'],
            'maintenance_request_id' => ['nullable', Rule::exists('maintenance_requests', 'id')->where('company_id', $this->user()->company_id)],
            'transaction_date' => ['required', 'date'],
        ];
    }
}
