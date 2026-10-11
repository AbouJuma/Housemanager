<?php

namespace App\Modules\PaymentReminders\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One configurable reminder rule - "N days before/after an invoice's or
 * installment's due date, send a reminder, optionally repeating every M
 * days after that". A company can define any number of these per
 * context (see the `context` column doc in the migration), so reminder
 * cadence is fully editable instead of hardcoded - see
 * App\Modules\PaymentReminders\Services\ReminderRuleService and the two
 * commands that consume it, App\Modules\RentCollection\Console\
 * Commands\SendRentReminders and
 * App\Modules\Sales\Console\Commands\SendPaymentReminders.
 */
class PaymentReminderRule extends Model
{
    use BelongsToCompany;
    use HasUuid;

    public const CONTEXT_RENT = 'rent';

    public const CONTEXT_SALE_INSTALLMENT = 'sale_installment';

    protected $fillable = [
        'company_id',
        'context',
        'offset_days',
        'repeat_every_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'offset_days' => 'integer',
        'repeat_every_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function isBeforeOrOnDueDate(): bool
    {
        return $this->offset_days >= 0;
    }

    public function isRecurring(): bool
    {
        return $this->repeat_every_days !== null;
    }

    public function scopeContext(Builder $query, string $context): Builder
    {
        return $query->where('context', $context);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
