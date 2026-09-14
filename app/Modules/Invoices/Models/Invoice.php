<?php

namespace App\Modules\Invoices\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Invoices\Database\Factories\InvoiceFactory;
use App\Modules\Leases\Models\Lease;
use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'lease_id',
        'sale_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'status',
        'total_amount',
        'amount_paid',
        'notes',
    ];

    protected $attributes = [
        'status' => 'draft',
        'total_amount' => 0,
        'amount_paid' => 0,
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(InvoicePaymentAttempt::class);
    }

    public function balanceDue(): float
    {
        return round((float) $this->total_amount - (float) $this->amount_paid, 2);
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'status', 'total_amount', 'amount_paid'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('invoice');
    }
}
