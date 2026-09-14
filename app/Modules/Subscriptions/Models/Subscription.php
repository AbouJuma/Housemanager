<?php

namespace App\Modules\Subscriptions\Models;

use App\Core\Concerns\HasUuid;
use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * No BelongsToCompany - Subscription is data ABOUT a company (managed by
 * the SaaS owner), not data a company's own users operate on the way
 * every tenant-owned resource elsewhere in this app is. Access control
 * is via SubscriptionPolicy (SaaS-owner-manages-all, a company may only
 * view its own), not the tenant global scope.
 *
 * No update()/no mutation of plan or dates after creation - changing
 * plans or renewing always creates a new row (see this table's migration
 * comment). SubscriptionService::changePlan()/renew() are the only ways
 * a new row is ever created; cancel() is the only in-place mutation
 * allowed, and only ever moves status to 'canceled'.
 */
class Subscription extends Model
{
    use HasFactory;
    use HasUuid;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'canceled_at',
    ];

    protected $attributes = [
        'status' => 'trialing',
    ];

    protected $casts = [
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'canceled_at' => 'datetime',
    ];

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['trialing', 'active'], true);
    }

    public function isCurrent(): bool
    {
        return $this->isActive() && $this->current_period_end->isFuture();
    }

    /**
     * Scopes to exactly one row per company - whichever has the highest
     * `id`, a valid proxy for "most recently created" since rows are
     * always inserted in chronological order and never backdated (the
     * same `MAX(id) GROUP BY company_id` reasoning `Company::
     * currentSubscription()` and `ExpireSubscriptions` both need - this
     * scope is the single shared source of truth for it, so any future
     * caller doesn't have to rediscover or re-derive the subquery).
     * `subscriptions.id` is qualified so a caller who joins another
     * table onto this query (e.g. `subscription_plans` for a revenue
     * report) doesn't hit an ambiguous-column error - the same trap
     * documented in Modules 26/27/29.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereIn('subscriptions.id', function ($sub): void {
            $sub->selectRaw('MAX(id)')->from('subscriptions')->groupBy('company_id');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'current_period_start', 'current_period_end'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('subscription');
    }
}
