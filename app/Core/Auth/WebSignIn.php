<?php

namespace App\Core\Auth;

use App\Models\User;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One sign-in that works from any of the web sign-in pages: the page's
 * own account type is tried first, then the others - so a staff member
 * who opens the tenant portal's page (or a landlord on the staff page)
 * still gets in, and lands on their own home screen.
 */
class WebSignIn
{
    /** @var array<string, array{0: class-string, 1: string, 2: string}> type => [model, guard, home route] */
    public const ACCOUNTS = [
        'staff' => [User::class, 'web', 'dashboard'],
        'tenant' => [Tenant::class, 'tenant', 'portal.dashboard'],
        'landlord' => [Landlord::class, 'landlord', 'landlord-portal.dashboard'],
        'buyer' => [Buyer::class, 'buyer', 'buyer-portal.dashboard'],
    ];

    public static function attempt(Request $request, string $preferred, string $identifier, string $password): ?RedirectResponse
    {
        $order = [$preferred, ...array_diff(array_keys(self::ACCOUNTS), [$preferred])];

        foreach ($order as $type) {
            [$model, $guard, $home] = self::ACCOUNTS[$type];

            if (! $account = LoginIdentity::attempt($model, $identifier, $password)) {
                continue;
            }

            Auth::guard($guard)->login($account, $request->boolean('remember'));
            $request->session()->regenerate();

            // "intended" only makes sense on the page's own account type -
            // a staff URL saved before signing in isn't a tenant's.
            return $type === $preferred
                ? redirect()->intended(route($home, absolute: false))
                : redirect()->route($home);
        }

        return null;
    }
}
