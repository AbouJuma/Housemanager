<?php

namespace App\Modules\Sales\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Properties\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Sale extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected static function newFactory()
    {
        return \App\Modules\Sales\Database\Factories\SaleFactory::new();
    }

    protected $fillable = [
        'company_id',
        'unit_id',
        'new_buyer_id',
        'sale_date',
        'total_price',
        'down_payment',
        'installment_period',
        'installment_amount',
        'status',
        'notes',
        'recorded_by_id',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'installment_amount' => 'decimal:2',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class, 'new_buyer_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('installment_number');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Modules\Invoices\Models\Invoice::class);
    }

    public function paidInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', 'paid');
    }

    public function pendingInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', 'pending');
    }

    public function overdueInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', 'overdue');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->installments()->sum('paid_amount') + $this->down_payment;
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->total_price - $this->total_paid);
    }

    public function getCompletionPercentageAttribute(): int
    {
        if ($this->total_price == 0) {
            return 0;
        }
        return (int) min(100, ($this->total_paid / $this->total_price) * 100);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->remaining_balance <= 0;
    }

    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['sale_date', 'total_price', 'down_payment', 'installment_period', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('sale');
    }
}
