<?php

namespace App\Modules\TenantPortal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * `lease_id`, not `property_id`/`unit_id` directly - the tenant picks
 * which of their OWN leases the issue concerns, and the controller
 * derives property_id/unit_id from that lease server-side. This makes
 * spoofing a property/unit the tenant has no relationship to
 * structurally impossible, rather than something a scoped `exists` rule
 * has to prevent after the fact (see ARCHITECTURE.md §19).
 */
class PortalStoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('tenant')->check();
    }

    public function rules(): array
    {
        $tenant = Auth::guard('tenant')->user();

        return [
            'lease_id' => ['required', Rule::exists('leases', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(['plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping', 'pest_control', 'general_contractor', 'utility_provider', 'security', 'other'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
        ];
    }
}
