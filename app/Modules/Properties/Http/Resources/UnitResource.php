<?php

namespace App\Modules\Properties\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Properties\Models\Unit
 */
class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'building_uuid' => $this->whenLoaded('building', fn () => $this->building->uuid),
            'unit_number' => $this->unit_number,
            'type' => $this->type,
            'status' => $this->status,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,
            'area_unit' => $this->area_unit,
            'rent_amount' => $this->rent_amount,
            // What rent_amount is per: monthly / quarterly / semi_annual / annual.
            'rent_period' => $this->rent_period ?? 'monthly',
            'description' => $this->description,
            // Only meaningful when status isn't 'vacant' - who's actually
            // renting this unit right now. See Unit::currentTenant()'s
            // docblock for how this avoids N+1 across a list of units.
            'tenant' => ($tenant = $this->currentTenant()) ? [
                'uuid' => $tenant->uuid,
                'name' => $tenant->name,
                'phone' => $tenant->phone,
            ] : null,
            // A unit is either rented (tenant, above) or sold (this) -
            // rarely both. total_paid/remaining_balance/
            // completion_percentage are Sale's own accessors, so this
            // always agrees with what the buyer/landlord payment
            // notifications and the buyer portal already show, including
            // a sale that's only partially paid.
            'sale' => ($sale = $this->currentSale()) ? [
                'uuid' => $sale->uuid,
                'status' => $sale->status,
                'buyer' => $sale->buyer ? ['uuid' => $sale->buyer->uuid, 'name' => $sale->buyer->name, 'phone' => $sale->buyer->phone] : null,
                'total_price' => (float) $sale->total_price,
                'total_paid' => $sale->total_paid,
                'remaining_balance' => $sale->remaining_balance,
                'completion_percentage' => $sale->completion_percentage,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
