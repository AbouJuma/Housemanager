<?php

namespace App\Modules\LandlordPortal\Services;

use App\Modules\Landlords\Models\Landlord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Same shape as App\Modules\TenantPortal\Services\TenantPortalService
 * (Module 26, ARCHITECTURE.md §19) - admin-side management of a
 * landlord's own portal login, not a BaseService/repository-backed CRUD
 * service since there's no separate "portal" resource of its own.
 */
class LandlordPortalService
{
    public function enableAccess(Landlord $landlord): string
    {
        if (! $landlord->email) {
            throw ValidationException::withMessages(['email' => 'This landlord needs an email address before portal access can be enabled.']);
        }

        $emailTaken = Landlord::withoutGlobalScope('company')
            ->whereNotNull('password')
            ->where('email', $landlord->email)
            ->where('id', '!=', $landlord->id)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages(['email' => 'Another landlord with portal access already uses this email address.']);
        }

        $plainPassword = Str::password(12);

        $landlord->forceFill(['password' => $plainPassword])->save();

        return $plainPassword;
    }

    public function disableAccess(Landlord $landlord): void
    {
        $landlord->forceFill(['password' => null, 'remember_token' => null])->save();
    }
}
