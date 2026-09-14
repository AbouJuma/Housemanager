<?php

namespace App\Modules\Reports\Services;

use App\Modules\Properties\Models\Unit;
use Illuminate\Support\Collection;

/**
 * Every report in this module is a pure aggregation over data other
 * modules already own - no new tables, no repository/service pair
 * following the CRUD BaseService shape, since there's nothing here to
 * create/update/delete. Company scoping comes for free from
 * BelongsToCompany's global scope on Unit/Property/Account/Invoice, the
 * same as every other query in this app - no companyId parameter needed.
 */
class RentRollReportService
{
    public function generate(): Collection
    {
        return Unit::query()
            ->with(['building.property', 'leases' => fn ($query) => $query->where('leases.status', 'active')->with('tenant')])
            ->join('buildings', 'buildings.id', '=', 'units.building_id')
            ->orderBy('buildings.name')
            ->orderBy('units.unit_number')
            ->select('units.*')
            ->get()
            ->map(function (Unit $unit) {
                $lease = $unit->leases->first();

                return [
                    'property' => $unit->building->property->name,
                    'building' => $unit->building->name,
                    'unit' => $unit->unit_number,
                    'status' => $unit->status,
                    'tenant' => $lease?->tenant?->name,
                    'lease_start' => $lease?->start_date?->toDateString(),
                    'lease_end' => $lease?->end_date?->toDateString(),
                    'rent_amount' => (float) ($lease?->rent_amount ?? $unit->rent_amount),
                ];
            });
    }
}
