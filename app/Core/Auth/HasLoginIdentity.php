<?php

namespace App\Core\Auth;

use Illuminate\Support\Str;

/**
 * For every model a person signs in as (User, Tenant, Landlord, Buyer):
 * keeps `login_phone` (digits of `phone`, used to find the account by
 * phone number) in step, and gives every account a unique `username`
 * if none was chosen. See LoginIdentity.
 */
trait HasLoginIdentity
{
    public static function bootHasLoginIdentity(): void
    {
        static::saving(function ($model): void {
            $model->login_phone = LoginIdentity::phoneDigits($model->phone);

            if (blank($model->username)) {
                $model->username = LoginIdentity::uniqueUsername(static::class, $model->name, $model->getKey());
            } else {
                $model->username = Str::lower(trim($model->username));
            }
        });
    }
}
