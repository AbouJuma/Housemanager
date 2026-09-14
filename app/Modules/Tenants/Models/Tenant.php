<?php

namespace App\Modules\Tenants\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Documents\Models\Document;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\Tenants\Database\Factories\TenantFactory;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Notifiable was added in Module 18 (Rent Collection) so tenants can
 * receive due-soon/overdue invoice reminders the same way User does
 * for TrialEndingSoon (ARCHITECTURE.md §10f) - `email`/`phone` already
 * match Laravel's default notification routing, and CompanySmsChannel
 * reads `company` directly (already provided by BelongsToCompany), so
 * no routeNotificationFor*() overrides were needed.
 *
 * Extends Illuminate\Foundation\Auth\User (the same base App\Models\User
 * itself extends), not the plain Eloquent Model, since Module 26 (Tenant
 * Portal) needs Tenant to be a real Authenticatable for its own 'tenant'
 * guard - see ARCHITECTURE.md §19. This is a drop-in base class swap:
 * that class already extends Model, so nothing about Tenant's existing
 * behavior changes for tenants who never get portal access.
 */
class Tenant extends Authenticatable implements HasMedia
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'email',
        'phone',
        'control_number',
        'secondary_phone',
        'date_of_birth',
        'identification_type',
        'identification_number',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'notes',
    ];

    // password/remember_token deliberately absent from $fillable - see
    // this column's migration comment. $hidden matches App\Models\User's
    // own list, for the same reason: never let either reach a Blade
    // {{ $tenant }} dump or a JSON response by accident.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        // See this column's migration comment for why this is
        // encrypted-but-not-masked, the same reasoning as
        // landlords.bank_details.
        'identification_number' => 'encrypted',
        'password' => 'hashed',
    ];

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(Invoice::class, Lease::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function photoUrl(): ?string
    {
        return $this->getFirstMediaUrl('photo') ?: null;
    }

    public function isBlacklisted(): bool
    {
        return $this->status === 'blacklisted';
    }

    public function hasPortalAccess(): bool
    {
        return $this->password !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // identification_number excluded deliberately - sensitive
            // PII doesn't belong in the activity log even as a
            // "field changed" entry (same allowlist pattern as
            // Landlord's bank_details/tax_id exclusion).
            ->logOnly(['name', 'type', 'email', 'phone', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('tenant');
    }
}
