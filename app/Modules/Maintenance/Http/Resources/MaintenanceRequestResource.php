<?php

namespace App\Modules\Maintenance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Maintenance\Models\MaintenanceRequest
 */
class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'reported_date' => $this->reported_date?->toDateString(),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'completed_date' => $this->completed_date?->toDateString(),
            'estimated_cost' => $this->estimated_cost !== null ? (float) $this->estimated_cost : null,
            'actual_cost' => $this->actual_cost !== null ? (float) $this->actual_cost : null,
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
            'expense' => $this->whenLoaded('expense', fn () => $this->expense ? [
                'uuid' => $this->expense->uuid,
                'amount' => (float) $this->expense->amount,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
