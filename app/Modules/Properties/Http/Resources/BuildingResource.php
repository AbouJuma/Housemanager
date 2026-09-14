<?php

namespace App\Modules\Properties\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Properties\Models\Building
 */
class BuildingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'property_uuid' => $this->whenLoaded('property', fn () => $this->property->uuid),
            'name' => $this->name,
            'floor_count' => $this->floor_count,
            'description' => $this->description,
            'status' => $this->status,
            'units_count' => $this->whenCounted('units'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
