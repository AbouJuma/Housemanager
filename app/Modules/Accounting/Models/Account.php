<?php

namespace App\Modules\Accounting\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Accounting\Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Debit-normal for asset/expense, credit-normal for
     * liability/equity/income - standard accounting sign convention.
     * Never filters by journal_entries.status: a voided entry's
     * reversal already contains the opposite debit/credit for every
     * line the original posted, so the sum nets to zero on its own -
     * excluding voided entries would double-correct (see
     * JournalEntryService::void(), ARCHITECTURE.md §12).
     *
     * Prefers `debit_total`/`credit_total` if already eager-loaded via
     * `withSum()` (AccountService::paginate() does this, to avoid two
     * SUM queries per row on a list page) and only falls back to a
     * live query for a single show() call.
     */
    public function balance(): float
    {
        $debits = (float) ($this->debit_total ?? $this->lines()->sum('debit'));
        $credits = (float) ($this->credit_total ?? $this->lines()->sum('credit'));
        $net = round($debits - $credits, 2);

        return in_array($this->type, ['asset', 'expense'], true) ? $net : -$net;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'type', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('account');
    }
}
