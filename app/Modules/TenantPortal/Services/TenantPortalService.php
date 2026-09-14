<?php

namespace App\Modules\TenantPortal\Services;

use App\Modules\Tenants\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin-side management of a tenant's own portal login - not a
 * BaseService/repository-backed CRUD service, since there's no separate
 * "portal" resource to list or paginate, just a handful of state
 * transitions on an existing Tenant. See ARCHITECTURE.md §19.
 */
class TenantPortalService
{
    /**
     * Generates a new random password, hashes and stores it (via the
     * `password` => 'hashed' cast, ARCHITECTURE.md §19), and returns the
     * plaintext exactly once - the caller is responsible for showing it
     * to the admin a single time and never persisting it anywhere.
     * Reused for both "enable" and "reset" - both are the same
     * operation from the model's point of view.
     */
    public function enableAccess(Tenant $tenant): string
    {
        if (! $tenant->email) {
            throw ValidationException::withMessages(['email' => 'This tenant needs an email address before portal access can be enabled.']);
        }

        $emailTaken = Tenant::withoutGlobalScope('company')
            ->whereNotNull('password')
            ->where('email', $tenant->email)
            ->where('id', '!=', $tenant->id)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages(['email' => 'Another tenant with portal access already uses this email address.']);
        }

        $plainPassword = Str::password(12);

        $tenant->forceFill(['password' => $plainPassword])->save();

        return $plainPassword;
    }

    public function disableAccess(Tenant $tenant): void
    {
        $tenant->forceFill(['password' => null, 'remember_token' => null])->save();
    }
}
