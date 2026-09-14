<?php

namespace App\Modules\Accounting\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Models\User;
use App\Modules\Accounting\Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JournalEntry extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'entry_date',
        'reference',
        'description',
        'source_type',
        'source_id',
        'status',
        'created_by_id',
    ];

    protected $attributes = [
        'status' => 'posted',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    protected static function newFactory(): JournalEntryFactory
    {
        return JournalEntryFactory::new();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Prefers `debit_total` if eager-loaded via `withSum()`
     * (JournalEntryService::paginate() does this) to avoid a lazy
     * `lines` load per row on a list page - falls back to the loaded
     * `lines` collection for a single show() call.
     */
    public function totalDebits(): float
    {
        return round((float) ($this->debit_total ?? $this->lines->sum('debit')), 2);
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['description', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('journal_entry');
    }
}
