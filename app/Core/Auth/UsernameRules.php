<?php

namespace App\Core\Auth;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Validation for the optional `username` field on the staff, tenant,
 * landlord and buyer forms. Left blank, one is generated from the name
 * (HasLoginIdentity). Uniqueness is case-insensitive and includes
 * soft-deleted accounts, matching the column's unique index.
 */
class UsernameRules
{
    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, mixed>
     */
    public static function for(string $modelClass, ?int $ignoreId = null): array
    {
        return [
            'nullable',
            'string',
            'min:3',
            'max:60',
            'regex:/^[A-Za-z0-9._-]+$/',
            function (string $attribute, mixed $value, Closure $fail) use ($modelClass, $ignoreId): void {
                if (is_string($value) && LoginIdentity::usernameTaken($modelClass, $value, $ignoreId)) {
                    $fail(__('The username ":username" is already taken - try another one.', ['username' => $value]));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'username.regex' => __('A username can only use letters, numbers, dots, dashes and underscores (no spaces).'),
            'username.min' => __('A username needs at least 3 characters.'),
        ];
    }
}
