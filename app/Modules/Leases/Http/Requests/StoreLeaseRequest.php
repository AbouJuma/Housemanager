<?php

namespace App\Modules\Leases\Http\Requests;

use App\Modules\Leases\Models\Lease;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lease::class);
    }

    public function rules(): array
    {
        return [
            // Scoped to the acting user's own company, same discipline as
            // properties.landlord_id (ARCHITECTURE.md §10b) - a bare FK
            // exists() check alone doesn't know about tenancy. Also
            // excludes blacklisted tenants and non-vacant units at the
            // validation layer, not just the dropdown's options, since a
            // direct API request could otherwise bypass the UI filter.
            'tenant_id' => ['required', Rule::exists('tenants', 'id')->where(
                fn ($query) => $query->where('company_id', $this->user()->company_id)->where('status', '!=', 'blacklisted')
            )],
            'unit_id' => ['required', Rule::exists('units', 'id')->where(
                fn ($query) => $query->where('company_id', $this->user()->company_id)->where('status', 'vacant')
            )],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'quarterly', 'semi_annual', 'annual'])],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
