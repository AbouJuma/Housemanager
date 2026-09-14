<?php

namespace App\Modules\Leases\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lease'));
    }

    public function rules(): array
    {
        // tenant_id/unit_id are deliberately not editable here - a
        // lease's tenant/unit pairing is fixed at creation; changing
        // either means ending this lease and creating a new one, not
        // mutating this record (see ARCHITECTURE.md §10d).
        return [
            'end_date' => ['required', 'date', 'after:'.$this->route('lease')->start_date->toDateString()],
            'billing_cycle' => ['required', Rule::in(['monthly', 'quarterly', 'semi_annual', 'annual'])],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'expired', 'terminated'])],
            'termination_reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
