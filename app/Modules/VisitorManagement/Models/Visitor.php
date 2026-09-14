<?php

namespace App\Modules\VisitorManagement\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Models\User;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\VisitorManagement\Database\Factories\VisitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Visitor extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'property_id',
        'unit_id',
        'tenant_id',
        'vendor_id',
        'visitor_name',
        'visitor_phone',
        'host_name',
        'purpose',
        'vehicle_plate',
        'badge_number',
        'check_in_at',
        'check_out_at',
        'notes',
        'checked_in_by_id',
        'checked_out_by_id',
    ];

    protected $attributes = [
        'purpose' => 'other',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    protected static function newFactory(): VisitorFactory
    {
        return VisitorFactory::new();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_id');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_id');
    }

    public function isCheckedIn(): bool
    {
        return $this->check_out_at === null;
    }

    public function hostDisplayName(): ?string
    {
        return $this->tenant?->name ?? $this->host_name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['visitor_name', 'purpose', 'check_in_at', 'check_out_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('visitor');
    }
}
