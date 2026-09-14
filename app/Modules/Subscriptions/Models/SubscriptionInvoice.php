<?php

namespace App\Modules\Subscriptions\Models;

use App\Core\Concerns\HasUuid;
use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Database\Factories\SubscriptionInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * No BelongsToCompany - the same reasoning as Subscription. This is the
 * SaaS owner's own invoice to a company for their platform subscription,
 * a distinct sequence/concept entirely from Module 17's `invoices`
 * table (a company billing its own tenants for rent).
 */
class SubscriptionInvoice extends Model
{
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'subscription_id',
        'invoice_number',
        'amount',
        'status',
        'issue_date',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected $attributes = [
        'status' => 'unpaid',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    protected static function newFactory(): SubscriptionInvoiceFactory
    {
        return SubscriptionInvoiceFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'unpaid' && $this->due_date->isPast();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'amount', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('subscription_invoice');
    }
}
