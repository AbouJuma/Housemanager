<?php

namespace App\Modules\Maintenance\Http\Requests;

use App\Modules\Maintenance\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MaintenanceRequest::class);
    }

    public function rules(): array
    {
        $categories = ['plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping', 'pest_control', 'general_contractor', 'utility_provider', 'security', 'other'];

        return [
            'property_id' => ['required', Rule::exists('properties', 'id')->where('company_id', $this->user()->company_id)],
            'unit_id' => ['nullable', Rule::exists('units', 'id')->where('company_id', $this->user()->company_id)],
            'tenant_id' => ['nullable', Rule::exists('tenants', 'id')->where('company_id', $this->user()->company_id)],
            'vendor_id' => ['nullable', Rule::exists('vendors', 'id')->where('company_id', $this->user()->company_id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in($categories)],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'reported_date' => ['required', 'date'],
            'scheduled_date' => ['nullable', 'date'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
