<?php

namespace App\Core\Auth;

use Illuminate\Database\Eloquent\Model;

/**
 * Gives a newly created staff user, tenant, landlord or buyer immediate
 * access to the web portal / mobile app: a starting password (their
 * phone number - see LoginIdentity::defaultPassword()) and the
 * {username}/{password}/{login_url} values their welcome message
 * carries.
 */
class AccountProvisioner
{
    /**
     * Sets the account's password (the given one, or the phone-number
     * default) and returns it in plain text, for the welcome message
     * and the one-time "share these details" notice. Never stored.
     */
    public function grantAccess(Model $account, ?string $password = null): string
    {
        $plain = filled($password) ? $password : LoginIdentity::defaultPassword($account->phone);

        // `password` is outside $fillable on portal models on purpose;
        // the 'hashed' cast hashes it on save.
        $account->forceFill(['password' => $plain])->save();

        return $plain;
    }

    /**
     * @return array{username: string, password: string, login_url: string}
     */
    public function credentials(Model $account, string $plainPassword, string $loginUrl): array
    {
        return [
            'username' => (string) $account->username,
            'password' => $plainPassword,
            'login_url' => $loginUrl,
        ];
    }

    public static function loginUrlFor(string $kind): string
    {
        return match ($kind) {
            'tenant' => route('portal.login'),
            'landlord' => route('landlord-portal.login'),
            'staff' => route('login'),
            // Buyers sign in through the mobile app only.
            default => config('company.mobile_app_url') ?: url('/'),
        };
    }
}
