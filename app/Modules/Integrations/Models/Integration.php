<?php

namespace App\Modules\Integrations\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use BelongsToCompany;
    use HasUuid;

    protected $fillable = [
        'company_id',
        'type',
        'provider',
        'mode',
        'is_active',
        'credentials',
        'webhook_url',
        'callback_url',
        'status',
        'last_tested_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        // Encrypted at rest with APP_KEY - see docs/ARCHITECTURE.md §1
        // "Secret storage". Never echo a decrypted value back into an
        // HTML input; the edit form only ever shows a masked placeholder.
        'credentials' => 'encrypted:array',
        'last_tested_at' => 'datetime',
    ];

    public function credential(string $key): ?string
    {
        return $this->credentials[$key] ?? null;
    }

    public function isConfigured(): bool
    {
        return $this->exists;
    }
}
