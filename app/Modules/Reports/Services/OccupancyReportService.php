<?php

namespace App\Modules\Reports\Services;

use App\Modules\Properties\Models\Property;
use Illuminate\Support\Collection;

class OccupancyReportService
{
    /** Property statuses the report can be filtered by. */
    public const STATUSES = ['active', 'inactive', 'under_maintenance', 'archived'];

    public function generate(?string $status = null): Collection
    {
        return Property::query()
            ->when(in_array($status, self::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->with('units')
            ->orderBy('name')
            ->get()
            ->map(function (Property $property) {
                $units = $property->units;
                $total = $units->count();
                $occupied = $units->reject(fn ($unit) => $unit->isVacant())->count();

                return [
                    'property' => $property->name,
                    'total_units' => $total,
                    'occupied' => $occupied,
                    'vacant' => $total - $occupied,
                    'occupancy_rate' => $total > 0 ? round($occupied / $total * 100, 1) : 0.0,
                ];
            });
    }
}
