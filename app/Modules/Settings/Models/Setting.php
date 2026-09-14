<?php

namespace App\Modules\Settings\Models;

use App\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasUuid;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
