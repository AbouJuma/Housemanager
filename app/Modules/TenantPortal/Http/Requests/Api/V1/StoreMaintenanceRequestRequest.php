<?php

namespace App\Modules\TenantPortal\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * API twin of App\Modules\TenantPortal\Http\Requests\
 * PortalStoreMaintenanceRequestRequest - same fields/ownership rule,
 * but authorize()/rules() read $this->user() (Sanctum-resolved, see
 * EnsureTokenIsTenant) instead of Auth::guard('tenant'), which is never
 * populated for a token-authenticated request.
 *
 * `lease_id` validates against `uuid`, not the internal numeric `id` -
 * unlike a Blade form (which can render a hidden numeric id from a
 * server-rendered <select>), the mobile app only ever sees a Lease's
 * uuid (LeaseResource never exposes the numeric id), so this is the
 * only value it could plausibly submit.
 */
class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenant = $this->user();

        return [
            'lease_id' => ['required', Rule::exists('leases', 'uuid')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(['plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping', 'pest_control', 'general_contractor', 'utility_provider', 'security', 'other'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
        ];
    }
}
