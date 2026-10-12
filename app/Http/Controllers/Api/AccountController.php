<?php

namespace App\Http\Controllers\Api;

use App\Core\Auth\LoginIdentity;
use App\Core\Auth\UsernameRules;
use App\Core\Tenancy\PermissionsTeam;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Companies\Support\BrandTheme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * "My account" for whoever holds the token - staff user, tenant,
 * landlord or buyer (the mobile app's More -> Profile screen). One
 * controller for all four, mounted under each role's own API prefix:
 * /auth/... for staff, /portal/{role}/... for the others.
 */
class AccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(self::payload($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        /** @var Model $account */
        $account = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [...UsernameRules::for($account::class, $account->getKey()), 'required'],
            'email' => array_filter([
                'nullable', 'email', 'max:255',
                // Staff emails are unique; portal accounts' aren't (see LoginIdentity).
                $account instanceof User ? Rule::unique('users', 'email')->ignore($account->getKey()) : null,
            ]),
            'phone' => ['nullable', 'string', 'max:32'],
        ], UsernameRules::messages());

        $account->fill($data)->save();

        return response()->json(self::payload($account->fresh()));
    }

    public function password(Request $request): JsonResponse
    {
        /** @var Model $account */
        $account = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        // The starting password is the phone number - accept it typed in
        // any of its common formats, like the login does.
        $currentOk = collect(LoginIdentity::passwordVariants($data['current_password']))
            ->contains(fn (string $variant) => Hash::check($variant, $account->password));

        if (! $currentOk) {
            throw ValidationException::withMessages(['current_password' => __('Your current password is not correct.')]);
        }

        $account->forceFill(['password' => $data['password']])->save();

        // Sign out other devices; this one keeps its token.
        $current = $account->currentAccessToken();
        $account->tokens()->when($current?->id, fn ($query) => $query->whereKeyNot($current->id))->delete();

        return response()->json(['message' => __('Your password has been changed.')]);
    }

    /**
     * Profile + (for staff) the permissions the app uses to decide which
     * tabs and actions to show - so a role created later works without
     * an app update.
     *
     * @return array<string, mixed>
     */
    public static function payload(Model $account): array
    {
        $profile = [
            'uuid' => $account->uuid,
            'name' => $account->name,
            'username' => $account->username,
            'email' => $account->email,
            'phone' => $account->phone,
        ];

        $payload = ['profile' => $profile, 'branding' => BrandTheme::forCompany($account->company)->toArray()];

        if ($account instanceof Landlord) {
            $payload['permissions'] = LandlordAccess::permissionsOf($account);
        }

        if ($account instanceof User) {
            $payload['permissions'] = self::permissionsOf($account);
            $payload['roles'] = $account->company_id
                ? PermissionsTeam::runAs($account->company_id, fn () => $account->fresh()->getRoleNames()->values()->all())
                : [];
        }

        return $payload;
    }

    /**
     * @return array<int, string>
     */
    public static function permissionsOf(User $user): array
    {
        if (! $user->company_id) {
            return [];
        }

        return PermissionsTeam::runAs(
            $user->company_id,
            fn () => $user->fresh()->getAllPermissions()->pluck('name')->sort()->values()->all()
        );
    }
}
