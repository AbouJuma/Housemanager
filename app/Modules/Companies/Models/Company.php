<?php

namespace App\Modules\Companies\Models;

use App\Core\Concerns\HasUuid;
use App\Models\User;
use App\Modules\Companies\Database\Factories\CompanyFactory;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends Model implements HasMedia
{
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'timezone',
        'locale',
        'currency_code',
        'primary_color',
        'secondary_color',
        'bank_name',
        'account_number',
        'status',
        'trial_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionInvoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /**
     * Whichever subscription row has the latest period start - a new
     * row is created on every plan change/renewal (Subscription's own
     * class doc explains why), so "current" is never a matter of
     * updating a single mutable row. `current_period_start` is a `date`
     * column (no time-of-day), so two rows created on the same calendar
     * day - e.g. a trial converted to a paid plan the same day it was
     * created - would tie on that column alone; ordering by `id` too
     * breaks the tie deterministically in favor of whichever row was
     * actually created most recently, since rows are always inserted in
     * chronological order and never backdated.
     */
    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()->latest('current_period_start')->latest('id')->first();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('favicon')->singleFile();
    }

    public function logoUrl(): ?string
    {
        return $this->getFirstMediaUrl('logo') ?: null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'email', 'phone', 'website', 'status', 'currency_code', 'timezone', 'locale'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('company');
    }
}
