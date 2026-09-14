<?php

namespace App\Modules\Expenses\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Expenses\Database\Factories\ExpenseFactory;
use App\Modules\Properties\Models\Property;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_id',
        'property_id',
        'vendor_id',
        'vendor_name',
        'description',
        'amount',
        'expense_date',
        'payment_status',
        'payment_method',
        'paid_on',
        'reference',
        'notes',
        'status',
        'recorded_by_id',
    ];

    protected $attributes = [
        'payment_status' => 'unpaid',
        'status' => 'active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'paid_on' => 'date',
    ];

    protected static function newFactory(): ExpenseFactory
    {
        return ExpenseFactory::new();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    /**
     * Falls back to the free-text vendor_name when no formal Vendor
     * record is linked - both fields coexist deliberately
     * (ARCHITECTURE.md §14), so display code always has one place to
     * ask "who was this paid to" regardless of which was used.
     */
    public function vendorDisplayName(): ?string
    {
        return $this->vendor?->name ?? $this->vendor_name;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['description', 'amount', 'payment_status', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('expense');
    }
}
