<?php

namespace App\Modules\Sales\Models;

use App\Core\Auth\HasLoginIdentity;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Sales\Database\Factories\BuyerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Notifiable added alongside the messaging consolidation so buyers can
 * receive welcome/payment notifications the same way Tenant/Landlord
 * do - `email`/`phone` match Laravel's default notification routing,
 * and CompanySmsChannel reads `company` directly via BelongsToCompany,
 * so no routeNotificationFor*() overrides are needed.
 *
 * Extends Illuminate\Foundation\Auth\User, not the plain Eloquent Model
 * - the same drop-in base class swap Tenant/Landlord got for their own
 * portals (ARCHITECTURE.md §19), now for App\Modules\BuyerPortal's
 * 'buyer' guard + Sanctum token auth (HasApiTokens) for the mobile app.
 */
class Buyer extends Authenticatable
{
    use BelongsToCompany;
    use HasLoginIdentity;
    use HasApiTokens;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'username',
        'email',
        'phone',
        'control_number',
        'address',
        'id_number',
        'status',
        'notes',
    ];

    // password/remember_token deliberately absent from $fillable - see
    // this column's migration comment. $hidden matches Tenant/Landlord's
    // own list, for the same reason: never let either reach a Blade
    // {{ $buyer }} dump or a JSON response by accident.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status' => 'string',
        'password' => 'hashed',
    ];

    // Not previously needed - HasFactory's default guesser only checks
    // database/factories/, not a module's own Database/Factories/
    // (Sale::newFactory() already had this same override for the same
    // reason). Buyer::factory() silently had no working factory at all
    // until now, simply because nothing had exercised it yet.
    protected static function newFactory(): BuyerFactory
    {
        return BuyerFactory::new();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'new_buyer_id');
    }

    public function hasPortalAccess(): bool
    {
        return $this->password !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('buyer');
    }
}
