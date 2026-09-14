<?php

namespace App\Modules\Vendors\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Modules\Documents\Models\Document;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Vendors\Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Vendor extends Model implements HasMedia
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'category',
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

    protected $casts = [
        // See this column's migration comment for why this is
        // encrypted-but-not-masked, unlike integrations.credentials -
        // same reasoning as landlords.bank_details (ARCHITECTURE.md §10b).
        'bank_details' => 'encrypted:array',
    ];

    protected static function newFactory(): VendorFactory
    {
        return VendorFactory::new();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
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
            // as a "field changed" entry (Module 5's allowlist pattern,
            // same as LandlordPolicy).
            ->logOnly(['name', 'type', 'category', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('vendor');
    }
}
