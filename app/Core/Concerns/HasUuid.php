<?php

namespace App\Core\Concerns;

use Illuminate\Support\Str;

/**
 * Gives a model a public, non-enumerable `uuid` identity used for
 * route binding and API exposure, while the internal primary key
 * stays an auto-increment integer for fast joins/indexes.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
