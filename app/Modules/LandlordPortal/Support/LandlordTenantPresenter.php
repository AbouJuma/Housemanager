<?php

namespace App\Modules\LandlordPortal\Support;

use App\Modules\Landlords\Models\Landlord;
use App\Modules\Leases\Models\Lease;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

/**
 * One shape for "a tenant as their landlord sees them", shared by the
 * portal pages and the app API: contact details plus the leases they
 * hold on THIS landlord's units only - never other landlords' units.
 */
class LandlordTenantPresenter
{
    /** Eager loads for a list of the landlord's tenants. */
    public static function with(Landlord $landlord): array
    {
        return [
            'leases' => fn ($query) => $query
                ->whereHas('unit.building.property', fn (Builder $property) => $property->where('landlord_id', $landlord->id))
                ->with('unit:id,uuid,unit_number,building_id', 'unit.building:id,name,property_id', 'unit.building.property:id,uuid,name')
                ->latest('start_date'),
        ];
    }

    public static function row(Tenant $tenant, Landlord $landlord): array
    {
        $leases = $tenant->leases;
        $current = $leases->firstWhere('status', 'active') ?? $leases->first();

        return [
            'uuid' => $tenant->uuid,
            'name' => $tenant->name,
            'username' => $tenant->username,
            'type' => $tenant->type,
            'status' => $tenant->status,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
            'added_by_you' => $tenant->landlord_id === $landlord->id,
            'leases_count' => $leases->count(),
            'current_lease' => $current ? self::lease($current) : null,
            'created_at' => $tenant->created_at?->toIso8601String(),
        ];
    }

    public static function detail(Tenant $tenant, Landlord $landlord): array
    {
        return self::row($tenant, $landlord) + [
            'emergency_contact_name' => $tenant->emergency_contact_name,
            'emergency_contact_phone' => $tenant->emergency_contact_phone,
            'leases' => $tenant->leases->map(fn (Lease $lease) => self::lease($lease))->values()->all(),
        ];
    }

    public static function lease(Lease $lease): array
    {
        return [
            'uuid' => $lease->uuid,
            'status' => $lease->status,
            'start_date' => $lease->start_date?->toDateString(),
            'end_date' => $lease->end_date?->toDateString(),
            'rent_amount' => (float) $lease->rent_amount,
            'unit_number' => $lease->unit?->unit_number,
            'building' => $lease->unit?->building?->name,
            'property' => $lease->unit?->building?->property
                ? ['uuid' => $lease->unit->building->property->uuid, 'name' => $lease->unit->building->property->name]
                : null,
        ];
    }
}
