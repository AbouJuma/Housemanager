<?php

namespace App\Http\Controllers;

use App\Core\Auth\LoginIdentity;
use App\Models\User;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live "is this username free?" check behind <x-username-field> - the
 * form shows the answer as you type; the same rule is enforced again on
 * submit (App\Core\Auth\UsernameRules).
 */
class UsernameAvailabilityController extends Controller
{
    protected const MODELS = [
        'staff' => User::class,
        'tenant' => Tenant::class,
        'landlord' => Landlord::class,
        'buyer' => Buyer::class,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:staff,tenant,landlord,buyer'],
            'username' => ['nullable', 'string', 'max:60'],
            'name' => ['nullable', 'string', 'max:255'],
            'ignore' => ['nullable', 'integer'],
        ]);

        $model = self::MODELS[$data['type']];
        $ignore = isset($data['ignore']) ? (int) $data['ignore'] : null;
        $username = trim((string) ($data['username'] ?? ''));

        // Nothing typed yet: just suggest one from the name.
        if ($username === '') {
            return response()->json([
                'available' => null,
                'suggestion' => filled($data['name'] ?? null) ? LoginIdentity::uniqueUsername($model, $data['name'], $ignore) : null,
            ]);
        }

        if (! preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
            return response()->json([
                'available' => false,
                'message' => 'Use at least 3 letters, numbers, dots, dashes or underscores (no spaces).',
            ]);
        }

        $taken = LoginIdentity::usernameTaken($model, $username, $ignore);

        return response()->json([
            'available' => ! $taken,
            'message' => $taken ? "\"{$username}\" is already taken." : "\"{$username}\" is available.",
            'suggestion' => $taken ? LoginIdentity::uniqueUsername($model, $username, $ignore) : null,
        ]);
    }
}
