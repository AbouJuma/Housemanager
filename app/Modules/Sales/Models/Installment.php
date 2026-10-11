<?php

namespace App\Modules\Sales\Models;

use App\Core\Concerns\HasUuid;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Installment extends Model
{
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'installment_number',
        'due_date',
        'amount',
        'status',
        'paid_amount',
        'paid_date',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // One invoice per installment, generated upfront when the schedule
    // is created (App\Modules\Sales\Services\SaleService::
    // generateInstallmentSchedule()) - a payment against this
    // installment updates that SAME invoice rather than creating a new
    // one (App\Modules\Sales\Services\SaleService::recordPayment()).
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->paid_amount >= $this->amount;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || ($this->status === 'pending' && $this->due_date < now()->startOfDay());
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid' || ($this->paid_amount > 0 && $this->paid_amount < $this->amount);
    }

    public function markAsPaid(float $amount = null): void
    {
        $this->paid_amount = $amount ?? $this->amount;
        $this->paid_date = now();
        $this->status = 'paid';
        $this->save();
    }

    public function markAsOverdue(): void
    {
        if ($this->status === 'pending') {
            $this->status = 'overdue';
            $this->save();
        }
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->amount - $this->paid_amount);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['due_date', 'amount', 'status', 'paid_amount', 'paid_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('installment');
    }
}
