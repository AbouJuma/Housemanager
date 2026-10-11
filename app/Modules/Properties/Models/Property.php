<?php

namespace App\Modules\Properties\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Documents\Models\Document;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Database\Factories\PropertyFactory;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Property extends Model implements HasMedia
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'landlord_id',
        'name',
        'type',
        'status',
        'description',
        'address_line1',
        'address_line2',
        'city',
        'area',
        'state',
        'postal_code',
        'country_code',
        'latitude',
        'longitude',
        'total_area',
        'area_unit',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'total_area' => 'decimal:2',
    ];

    protected static function newFactory(): PropertyFactory
    {
        return PropertyFactory::new();
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function units(): HasManyThrough
    {
        return $this->hasManyThrough(Unit::class, Building::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
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
        $this->addMediaCollection('photos');
    }

    public function coverPhotoUrl(): ?string
    {
        return $this->getFirstMediaUrl('photos') ?: null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'status', 'city', 'country_code'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('property');
    }
}
