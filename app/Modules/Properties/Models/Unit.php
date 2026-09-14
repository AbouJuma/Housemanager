<?php

namespace App\Modules\Properties\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Database\Factories\UnitFactory;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Unit extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'building_id',
        'unit_number',
        'type',
        'status',
        'bedrooms',
        'bathrooms',
        'area',
        'area_unit',
        'rent_amount',
        'description',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'rent_amount' => 'decimal:2',
    ];

    protected static function newFactory(): UnitFactory
    {
        return UnitFactory::new();
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function currentLease(): ?Lease
    {
        return $this->leases()->where('status', 'active')->first();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function activeSale(): ?Sale
    {
        return $this->sales()->whereIn('status', ['active', 'completed'])->first();
    }

    public function isVacant(): bool
    {
        return $this->status === 'vacant';
    }

    public function isAvailableForRent(): bool
    {
        return $this->isVacant() && !$this->activeSale();
    }

    public function isAvailableForSale(): bool
    {
        return $this->isVacant() && !$this->currentLease() && !$this->activeSale();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['unit_number', 'type', 'status', 'rent_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('unit');
    }
}
