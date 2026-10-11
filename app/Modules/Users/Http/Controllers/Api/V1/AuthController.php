<?php

namespace App\Modules\Users\Http\Controllers\Api\V1;

use App\Core\Auth\LoginIdentity;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Companies\Support\BrandTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Token issuance for staff (the mobile app) - deliberately NOT
 * Auth::attempt() (what the web login uses via App\Http\Requests\Auth\
 * LoginRequest): that logs the 'web' session guard in as a side effect,
 * which is pointless for a stateless token client and, worse, can leave
 * a lingering session that makes Sanctum's guard prefer it over the
 * bearer token on a later request (surfaced by testing: a session left
 * over from login caused $request->user()->currentAccessToken() to
 * return a TransientToken with no delete() method instead of the real
 * token). Manual credential check instead, matching the same pattern
 * already used by the tenant/landlord/buyer portal logins.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            // Email, username or phone number.
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|staff|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        // Email, username or phone number - see App\Core\Auth\LoginIdentity.
        $user = LoginIdentity::attempt(User::class, $credentials['email'], $credentials['password']);

        if (! $user) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        // A SaaS owner (company_id === null - see User::isSaasOwner()) has
        // no company for the every-endpoint-is-company-scoped policies
        // (TenantPolicy et al.'s "no-SaaS-owner-bypass" pattern,
        // ARCHITECTURE.md §10a/§10b) and EnsureCompanyIsActive to check
        // against - every single company-scoped screen would 403 for
        // them despite a "successful" login. Reject it here with one
        // clear message instead of handing back a token that then fails
        // silently and confusingly on every subsequent request.
        if ($user->isSaasOwner()) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'This app is for company staff accounts. Super Admin/platform accounts are not supported here.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_saas_owner' => $user->isSaasOwner(),
            ],
            // Which tabs/actions the app shows - any role, including ones
            // a company creates later (see AccountController::payload()).
            'permissions' => AccountController::permissionsOf($user),
            'branding' => BrandTheme::forCompany($user->company)->toArray(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
