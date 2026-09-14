<?php

namespace App\Modules\Subscriptions\Models;

use App\Core\Concerns\HasUuid;
use App\Modules\Subscriptions\Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * No BelongsToCompany - a platform-level catalog the SaaS owner
 * maintains, not data owned by a tenant company (the same reasoning
 * Company itself isn't BelongsToCompany-scoped either).
 */
class SubscriptionPlan extends Model
{
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'max_properties',
        'max_units',
        'max_users',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'billing_cycle' => 'monthly',
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): SubscriptionPlanFactory
    {
        return SubscriptionPlanFactory::new();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function limitLabel(?int $limit): string
    {
        return $limit === null ? 'Unlimited' : (string) $limit;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'billing_cycle', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('subscription_plan');
    }
}
