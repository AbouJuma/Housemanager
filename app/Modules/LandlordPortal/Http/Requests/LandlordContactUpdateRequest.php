<?php

namespace App\Modules\LandlordPortal\Http\Requests;

use App\Core\Auth\UsernameRules;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A landlord correcting the contact details of one of their tenants or
 * buyers (route parameter `tenant` or `buyer`). Only people linked to
 * the landlord's own properties can be edited.
 */
class LandlordContactUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $landlord = $this->user();
        if ($tenant = $this->route('tenant')) {
            return LandlordAccess::can($landlord, 'tenants.update') && $landlord->tenantsQuery()->whereKey($tenant->id)->exists();
        }
        if ($buyer = $this->route('buyer')) {
            return LandlordAccess::can($landlord, 'buyers.update') && $landlord->buyersQuery()->whereKey($buyer->id)->exists();
        }

        return false;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $model = $tenant ? Tenant::class : Buyer::class;
        $id = ($tenant ?? $this->route('buyer'))?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => UsernameRules::for($model, $id),
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'status' => ['nullable', Rule::in($tenant ? ['active', 'inactive'] : ['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            ...($tenant ? [
                'emergency_contact_name' => ['nullable', 'string', 'max:255'],
                'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
            ] : [
                'address' => ['nullable', 'string', 'max:500'],
                'id_number' => ['nullable', 'string', 'max:50'],
            ]),
        ];
    }

    public function messages(): array
    {
        return UsernameRules::messages();
    }

    /** Username left blank keeps the current one. */
    public function validatedDetails(): array
    {
        return collect($this->validated())->reject(fn ($value, $key) => $key === 'username' && blank($value))->all();
    }
}
