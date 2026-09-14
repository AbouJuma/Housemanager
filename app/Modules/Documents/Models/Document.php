<?php

namespace App\Modules\Documents\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Models\User;
use App\Modules\Documents\Database\Factories\DocumentFactory;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Property;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'documentable_type',
        'documentable_id',
        'title',
        'category',
        'description',
        'expiry_date',
        'uploaded_by_id',
    ];

    protected $attributes = [
        'category' => 'other',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }

    /**
     * The short, form-facing keys a document can be attached to, mapped
     * to their real morph classes - the app stores the raw FQCN in
     * documentable_type (the same convention journal_entries.source_type
     * already uses, ARCHITECTURE.md §12), but requests/views work with
     * these short keys instead so validation and route-building don't
     * need to know class names.
     *
     * @return array<string, class-string>
     */
    public static function documentableTypeMap(): array
    {
        return [
            'property' => Property::class,
            'landlord' => Landlord::class,
            'tenant' => Tenant::class,
            'lease' => Lease::class,
            'vendor' => Vendor::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function documentableRouteMap(): array
    {
        return [
            'property' => 'properties.show',
            'landlord' => 'landlords.show',
            'tenant' => 'tenants.show',
            'lease' => 'leases.show',
            'vendor' => 'vendors.show',
        ];
    }

    public function documentableTypeKey(): ?string
    {
        if (! $this->documentable_type) {
            return null;
        }

        return array_search($this->documentable_type, self::documentableTypeMap(), true) ?: null;
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->lte(now()->addDays($withinDays));
    }

    public function fileUrl(): ?string
    {
        return $this->getFirstMediaUrl('file') ?: null;
    }

    public function fileName(): ?string
    {
        return $this->getFirstMedia('file')?->file_name;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'category', 'expiry_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('document');
    }
}
