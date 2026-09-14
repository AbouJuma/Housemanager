<?php

namespace App\Modules\Leases\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Documents\Models\Document;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Database\Factories\LeaseFactory;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lease extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    /**
     * Mirrors the migration's column defaults in PHP - without this,
     * a freshly created Lease that didn't explicitly set `status`
     * (e.g. StoreLeaseRequest deliberately never accepts it - every
     * new lease starts active, see ARCHITECTURE.md §10d) has a null
     * `status` attribute in memory even though the DB applied its
     * default, which would silently break LeaseService::syncUnitStatus()'s
     * `=== 'active'` check right after create().
     */
    protected $attributes = [
        'status' => 'active',
        'billing_cycle' => 'monthly',
    ];

    protected $fillable = [
        'company_id',
        'tenant_id',
        'unit_id',
        'start_date',
        'end_date',
        'billing_cycle',
        'rent_amount',
        'deposit_amount',
        'status',
        'terminated_at',
        'termination_reason',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'terminated_at' => 'date',
        'rent_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    protected static function newFactory(): LeaseFactory
    {
        return LeaseFactory::new();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'start_date', 'end_date', 'rent_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('lease');
    }
}
