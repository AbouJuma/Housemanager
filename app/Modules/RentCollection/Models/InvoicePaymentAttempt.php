<?php

namespace App\Modules\RentCollection\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Models\User;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\RentCollection\Database\Factories\InvoicePaymentAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePaymentAttempt extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'provider',
        'reference',
        'amount_minor',
        'currency',
        'status',
        'message',
        'initiated_by_id',
        'meta',
        'verified_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'meta' => 'array',
        'verified_at' => 'datetime',
    ];

    protected static function newFactory(): InvoicePaymentAttemptFactory
    {
        return InvoicePaymentAttemptFactory::new();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_id');
    }

    public function amountMajor(): float
    {
        return round($this->amount_minor / 100, 2);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
