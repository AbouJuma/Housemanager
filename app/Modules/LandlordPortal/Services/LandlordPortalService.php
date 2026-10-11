<?php

namespace App\Modules\LandlordPortal\Services;

use App\Core\Auth\LoginIdentity;
use App\Modules\Landlords\Models\Landlord;
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

        $emailTaken = $landlord->email && Landlord::withoutGlobalScope('company')
            ->whereNotNull('password')
            ->where('email', $landlord->email)
            ->where('id', '!=', $landlord->id)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages(['email' => 'Another landlord with portal access already uses this email address.']);
        }

        // Same starting password a new account gets: their phone number
        // (random when there's no usable phone) - see LoginIdentity.
        $plainPassword = LoginIdentity::defaultPassword($landlord->phone);

        $landlord->forceFill(['password' => $plainPassword])->save();

        return $plainPassword;
    }

    public function disableAccess(Landlord $landlord): void
    {
        $landlord->forceFill(['password' => null, 'remember_token' => null])->save();
    }
}
