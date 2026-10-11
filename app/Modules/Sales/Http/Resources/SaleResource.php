<?php

namespace App\Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Sales\Models\Sale
 */
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'sale_date' => $this->sale_date?->toDateString(),
            'total_price' => (float) $this->total_price,
            'down_payment' => (float) $this->down_payment,
            'installment_period' => $this->installment_period,
            'installment_amount' => (float) $this->installment_amount,
            'status' => $this->status,
            'total_paid' => $this->total_paid,
            'remaining_balance' => $this->remaining_balance,
            'completion_percentage' => $this->completion_percentage,
            'notes' => $this->notes,
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
            'installments' => InstallmentResource::collection($this->whenLoaded('installments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
