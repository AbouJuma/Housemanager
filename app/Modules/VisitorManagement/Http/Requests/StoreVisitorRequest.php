<?php

namespace App\Modules\VisitorManagement\Http\Requests;

use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Visitor::class);
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', Rule::exists('properties', 'id')->where('company_id', $this->user()->company_id)],
            'unit_id' => ['nullable', Rule::exists('units', 'id')->where('company_id', $this->user()->company_id)],
            'tenant_id' => ['nullable', Rule::exists('tenants', 'id')->where('company_id', $this->user()->company_id)],
            'vendor_id' => ['nullable', Rule::exists('vendors', 'id')->where('company_id', $this->user()->company_id)],
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:50'],
            'host_name' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', Rule::in(['delivery', 'meeting', 'maintenance', 'interview', 'personal', 'other'])],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'badge_number' => ['nullable', 'string', 'max:50'],
            'check_in_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
