<?php

namespace App\Modules\BuyerPortal\Services;

use App\Core\Auth\LoginIdentity;
use App\Modules\Sales\Models\Buyer;
use Illuminate\Validation\ValidationException;

/**
 * Admin-side management of a buyer's own portal login - mirrors
 * App\Modules\TenantPortal\Services\TenantPortalService exactly. Not a
 * BaseService/repository-backed CRUD service, since there's no separate
 * "portal" resource to list or paginate, just a handful of state
 * transitions on an existing Buyer.
 */
class BuyerPortalService
{
    /**
     * Generates a new random password, hashes and stores it (via the
     * `password` => 'hashed' cast), and returns the plaintext exactly
     * once - the caller is responsible for showing it to the admin a
     * single time and never persisting it anywhere. Reused for both
     * "enable" and "reset" - both are the same operation from the
     * model's point of view.
     */
    public function enableAccess(Buyer $buyer): string
    {

        $emailTaken = $buyer->email && Buyer::withoutGlobalScope('company')
            ->whereNotNull('password')
            ->where('email', $buyer->email)
            ->where('id', '!=', $buyer->id)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages(['email' => 'Another buyer with portal access already uses this email address.']);
        }

        // Same starting password a new account gets: their phone number
        // (random when there's no usable phone) - see LoginIdentity.
        $plainPassword = LoginIdentity::defaultPassword($buyer->phone);

        $buyer->forceFill(['password' => $plainPassword])->save();

        return $plainPassword;
    }

    public function disableAccess(Buyer $buyer): void
    {
        $buyer->forceFill(['password' => null, 'remember_token' => null])->save();
    }
}
