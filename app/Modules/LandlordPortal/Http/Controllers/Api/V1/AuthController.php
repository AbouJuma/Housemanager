<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Core\Auth\LoginIdentity;
use App\Http\Controllers\Controller;
use App\Modules\Companies\Support\BrandTheme;
use App\Modules\LandlordPortal\Http\Requests\LandlordPortalLoginRequest;
use App\Modules\Landlords\Http\Resources\LandlordResource;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Token issuance for the mobile app's landlord login - mirrors
 * App\Modules\TenantPortal\Http\Controllers\Api\V1\AuthController
 * exactly, scoped to Landlord.
 */
class AuthController extends Controller
{
    public function login(LandlordPortalLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $throttleKey = Str::lower($credentials['email']).'|landlord|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        // Email, username or phone number - see App\Core\Auth\LoginIdentity.
        $landlord = LoginIdentity::attempt(Landlord::class, $credentials['email'], $credentials['password']);

        if (! $landlord) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        RateLimiter::clear($throttleKey);

        return response()->json([
            'token' => $landlord->createToken('mobile')->plainTextToken,
            'landlord' => LandlordResource::make($landlord),
            // What the company's Landlord role allows - the app shows only these.
            'permissions' => LandlordAccess::permissionsOf($landlord),
            'branding' => BrandTheme::forCompany($landlord->company)->toArray(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
