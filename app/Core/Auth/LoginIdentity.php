<?php

namespace App\Core\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * How staff, tenants, landlords and buyers sign in - by email, username
 * or phone number - and the credentials a new account starts with.
 *
 * Phone numbers are compared on their last 9 digits, so "0712 345 678",
 * "+255712345678" and "255712345678" are the same login. A new account's
 * default password is its phone number (digits only), and typing the
 * phone in any of those formats as the password works too.
 */
class LoginIdentity
{
    /** Digits compared when matching a phone number (East African numbers are 9 after the prefix). */
    public const PHONE_TAIL = 9;

    public static function phoneDigits(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) >= 6 ? $digits : null;
    }

    public static function phoneTail(?string $phone): ?string
    {
        $digits = self::phoneDigits($phone);

        return $digits ? substr($digits, -self::PHONE_TAIL) : null;
    }

    /**
     * A new account's starting password: its phone number (digits only),
     * or - with no usable phone - a random one. Always non-empty, so the
     * welcome message can always tell the person how to sign in.
     */
    public static function defaultPassword(?string $phone): string
    {
        return self::phoneDigits($phone) ?? Str::password(10, symbols: false);
    }

    /**
     * "Amina Mushi" -> "amina.mushi", made unique within the model's
     * table (soft-deleted rows included - the column is unique).
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function uniqueUsername(string $modelClass, ?string $name, ?int $ignoreId = null): string
    {
        $base = Str::of((string) $name)->ascii()->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->limit(30, '')
            ->toString();

        if (strlen($base) < 3) {
            $base = 'user'.($base !== '' ? '.'.$base : '');
        }

        $candidate = $base;
        $suffix = 1;

        while (self::usernameTaken($modelClass, $candidate, $ignoreId)) {
            $suffix++;
            $candidate = $base.$suffix;
        }

        return $candidate;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function usernameTaken(string $modelClass, string $username, ?int $ignoreId = null): bool
    {
        $query = $modelClass::query()->withoutGlobalScopes()->where('username', Str::lower($username));

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        return $query->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists();
    }

    /**
     * Finds the account the identifier + password belong to, or null.
     * Only accounts that have a password (= portal/app access) qualify.
     * Several accounts may share a phone number; the password decides.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel|null
     */
    public static function attempt(string $modelClass, string $identifier, string $password): ?Model
    {
        $identifier = trim($identifier);
        $tail = self::phoneTail($identifier);

        $candidates = $modelClass::query()
            ->withoutGlobalScope('company')
            ->whereNotNull('password')
            ->where(function ($query) use ($identifier, $tail): void {
                $query->where('email', $identifier)
                    ->orWhere('username', Str::lower($identifier));

                // Only treat it as a phone when it actually looks like one.
                if ($tail && preg_match('/^[\d\s+()\-]+$/', $identifier)) {
                    $query->orWhere('login_phone', 'like', '%'.$tail);
                }
            })
            ->limit(20)
            ->get();

        foreach ($candidates as $account) {
            foreach (self::passwordVariants($password) as $variant) {
                if (Hash::check($variant, $account->password)) {
                    return $account;
                }
            }
        }

        return null;
    }

    /**
     * What the person typed, plus - when it looks like a phone number -
     * the same number in the other common formats, since the default
     * password is "the phone number" and people type it differently.
     *
     * @return array<int, string>
     */
    public static function passwordVariants(string $password): array
    {
        $variants = [$password];
        $digits = preg_replace('/\D+/', '', $password);

        if (preg_match('/^[\d\s+()\-]+$/', $password) && strlen($digits) >= 9) {
            $tail = substr($digits, -self::PHONE_TAIL);
            array_push($variants, $digits, '0'.$tail, '255'.$tail, '254'.$tail, '256'.$tail);
        }

        return array_values(array_unique($variants));
    }
}
