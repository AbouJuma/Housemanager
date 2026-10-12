<?php

namespace App\Modules\Landlords\Models;

use App\Core\Auth\HasLoginIdentity;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Documents\Models\Document;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Landlords\Database\Factories\LandlordFactory;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Extends Illuminate\Foundation\Auth\User, not the plain Eloquent Model
 * - the same drop-in base class swap Tenant got in Module 26
 * (ARCHITECTURE.md §19), for the identical reason: Module 27 (Landlord
 * Portal) needs Landlord to be a real Authenticatable for its own
 * 'landlord' guard.
 *
 * Notifiable added alongside the messaging consolidation so landlords
 * can receive welcome/new-tenant/payment notifications the same way
 * Tenant already does (`email`/`phone` match Laravel's default
 * notification routing, and CompanySmsChannel reads `company` directly
 * via BelongsToCompany, so no routeNotificationFor*() overrides needed).
 */
class Landlord extends Authenticatable implements HasMedia
{
    use BelongsToCompany;
    use HasLoginIdentity;
    use HasApiTokens;
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'username',
        'type',
        'email',
        'phone',
        'secondary_phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'tax_id',
        'bank_details',
        'status',
        'notes',
    ];

    // password/remember_token deliberately absent - see this column's
    // migration comment. $hidden matches Tenant's own list.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        // See this column's migration comment for why this is
        // encrypted-but-not-masked, unlike integrations.credentials.
        'bank_details' => 'encrypted:array',
        'password' => 'hashed',
    ];

    protected static function newFactory(): LandlordFactory
    {
        return LandlordFactory::new();
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function expenses(): HasManyThrough
    {
        return $this->hasManyThrough(Expense::class, Property::class);
    }

    public function maintenanceRequests(): HasManyThrough
    {
        return $this->hasManyThrough(MaintenanceRequest::class, Property::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Tenants this landlord deals with: anyone who has (or had) a lease on
     * one of their units, plus tenants they added themselves.
     */
    public function tenantsQuery(): Builder
    {
        return Tenant::query()->where(fn (Builder $query) => $query
            ->where('tenants.landlord_id', $this->id)
            ->orWhereHas('leases.unit.building.property', fn (Builder $property) => $property->where('landlord_id', $this->id)));
    }

    /** Units in this landlord's properties. */
    public function unitsQuery(): Builder
    {
        return Unit::query()->whereHas('building.property', fn (Builder $property) => $property->where('landlord_id', $this->id));
    }

    public function hasPortalAccess(): bool
    {
        return $this->password !== null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function photoUrl(): ?string
    {
        return $this->getFirstMediaUrl('photo') ?: null;
    }

    public function bankDetail(string $key): ?string
    {
        return $this->bank_details[$key] ?? null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // bank_details/tax_id excluded deliberately - sensitive
            // financial data doesn't belong in the activity log even
            // as a "field changed" entry (Module 5's allowlist pattern).
            ->logOnly(['name', 'type', 'email', 'phone', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('landlord');
    }
}
