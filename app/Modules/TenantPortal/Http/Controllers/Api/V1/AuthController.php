<?php

namespace App\Modules\TenantPortal\Http\Controllers\Api\V1;

use App\Core\Auth\LoginIdentity;
use App\Http\Controllers\Controller;
use App\Modules\Companies\Support\BrandTheme;
use App\Modules\TenantPortal\Http\Requests\PortalLoginRequest;
use App\Modules\Tenants\Http\Resources\TenantResource;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Token issuance for the mobile app's tenant login - the same manual
 * credential lookup as App\Modules\TenantPortal\Http\Controllers\
 * PortalAuthController::login() (tenants.email has no global uniqueness
 * constraint, only among portal-enabled tenants - see that controller's
 * docblock), just issuing a Sanctum token instead of a session.
 */
class AuthController extends Controller
{
    public function login(PortalLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $throttleKey = Str::lower($credentials['email']).'|tenant|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        // Email, username or phone number - see App\Core\Auth\LoginIdentity.
        $tenant = LoginIdentity::attempt(Tenant::class, $credentials['email'], $credentials['password']);

        if (! $tenant) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        RateLimiter::clear($throttleKey);

        return response()->json([
            'token' => $tenant->createToken('mobile')->plainTextToken,
            'tenant' => TenantResource::make($tenant),
            'branding' => BrandTheme::forCompany($tenant->company)->toArray(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
