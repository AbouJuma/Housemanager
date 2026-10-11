<?php

namespace App\Modules\BuyerPortal\Http\Controllers\Api\V1;

use App\Core\Auth\LoginIdentity;
use App\Http\Controllers\Controller;
use App\Modules\BuyerPortal\Http\Requests\BuyerPortalLoginRequest;
use App\Modules\Companies\Support\BrandTheme;
use App\Modules\Sales\Http\Resources\BuyerResource;
use App\Modules\Sales\Models\Buyer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Token issuance for the mobile app's buyer login - mirrors
 * App\Modules\TenantPortal\Http\Controllers\Api\V1\AuthController
 * exactly, scoped to Buyer. Unlike Tenant/Landlord, there is no
 * session-based web login to also mirror - Sanctum tokens are this
 * portal's only auth mechanism, see App\Modules\BuyerPortal\Providers\
 * BuyerPortalServiceProvider's docblock.
 */
class AuthController extends Controller
{
    public function login(BuyerPortalLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $throttleKey = Str::lower($credentials['email']).'|buyer|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        // Email, username or phone number - see App\Core\Auth\LoginIdentity.
        $buyer = LoginIdentity::attempt(Buyer::class, $credentials['email'], $credentials['password']);

        if (! $buyer) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        RateLimiter::clear($throttleKey);

        return response()->json([
            'token' => $buyer->createToken('mobile')->plainTextToken,
            'buyer' => BuyerResource::make($buyer),
            'branding' => BrandTheme::forCompany($buyer->company)->toArray(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
