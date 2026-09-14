<?php

namespace App\Modules\Leases\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Leases\Models\Lease
 */
class LeaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'billing_cycle' => $this->billing_cycle,
            'rent_amount' => (float) $this->rent_amount,
            'deposit_amount' => $this->deposit_amount !== null ? (float) $this->deposit_amount : null,
            'terminated_at' => $this->terminated_at?->toDateString(),
            'termination_reason' => $this->termination_reason,
            'notes' => $this->notes,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'uuid' => $this->tenant->uuid,
                'name' => $this->tenant->name,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'uuid' => $this->unit->uuid,
                'unit_number' => $this->unit->unit_number,
                'building' => $this->unit->relationLoaded('building') ? [
                    'uuid' => $this->unit->building->uuid,
                    'name' => $this->unit->building->name,
                    'property' => $this->unit->building->relationLoaded('property') ? [
                        'uuid' => $this->unit->building->property->uuid,
                        'name' => $this->unit->building->property->name,
                    ] : null,
                ] : null,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
