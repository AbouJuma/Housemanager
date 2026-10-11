<?php

namespace App\Modules\Tenants\Http\Requests;

use App\Core\Auth\UsernameRules;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Tenant::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Optional - generated from the name when blank.
            'username' => UsernameRules::for(Tenant::class, null),
            'type' => ['required', Rule::in(['individual', 'company'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'blacklisted'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'control_number' => ['nullable', 'string', 'max:64', Rule::unique('tenants', 'control_number')],
            'secondary_phone' => ['nullable', 'string', 'max:32'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'identification_type' => ['nullable', Rule::in(['national_id', 'passport', 'driving_license', 'voter_id', 'other'])],
            'identification_number' => ['nullable', 'string', 'max:64'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return UsernameRules::messages();
    }
}
