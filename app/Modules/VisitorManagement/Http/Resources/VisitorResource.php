<?php

namespace App\Modules\VisitorManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\VisitorManagement\Models\Visitor
 */
class VisitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'visitor_name' => $this->visitor_name,
            'visitor_phone' => $this->visitor_phone,
            'host_display_name' => $this->hostDisplayName(),
            'purpose' => $this->purpose,
            'vehicle_plate' => $this->vehicle_plate,
            'badge_number' => $this->badge_number,
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'check_out_at' => $this->check_out_at?->toIso8601String(),
            'is_checked_in' => $this->isCheckedIn(),
            'notes' => $this->notes,
            'property' => $this->whenLoaded('property', fn () => [
                'uuid' => $this->property->uuid,
                'name' => $this->property->name,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => $this->unit ? [
                'uuid' => $this->unit->uuid,
                'unit_number' => $this->unit->unit_number,
            ] : null),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'uuid' => $this->tenant->uuid,
                'name' => $this->tenant->name,
            ] : null),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'uuid' => $this->vendor->uuid,
                'name' => $this->vendor->name,
            ] : null),
            'checked_in_by' => $this->whenLoaded('checkedInBy', fn () => $this->checkedInBy?->name),
            'checked_out_by' => $this->whenLoaded('checkedOutBy', fn () => $this->checkedOutBy?->name),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
