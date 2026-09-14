<?php

namespace App\Modules\Properties\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Properties\Models\Property
 */
class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'landlord' => $this->whenLoaded('landlord', fn () => $this->landlord ? [
                'uuid' => $this->landlord->uuid,
                'name' => $this->landlord->name,
            ] : null),
            'description' => $this->description,
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country_code' => $this->country_code,
            ],
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'total_area' => $this->total_area,
            'area_unit' => $this->area_unit,
            'cover_photo_url' => $this->coverPhotoUrl(),
            'buildings_count' => $this->whenCounted('buildings'),
            'units_count' => $this->whenCounted('units'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
