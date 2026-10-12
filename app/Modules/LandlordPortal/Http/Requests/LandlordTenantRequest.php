<?php

namespace App\Modules\LandlordPortal\Http\Requests;

use App\Core\Auth\UsernameRules;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A landlord adding a tenant from the portal or the app. */
class LandlordTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return LandlordAccess::can($this->user(), 'tenants.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => UsernameRules::for(Tenant::class, null),
            'type' => ['required', Rule::in(['individual', 'company'])],
            'email' => ['nullable', 'email', 'max:255'],
            // The tenant signs in with it, and it's their first password.
            'phone' => ['required', 'string', 'max:32'],
            'identification_type' => ['nullable', Rule::in(['national_id', 'passport', 'driving_license', 'voter_id', 'other'])],
            'identification_number' => ['nullable', 'string', 'max:64'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return UsernameRules::messages();
    }
}
