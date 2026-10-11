<?php

namespace App\Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Sales\Models\Installment
 */
class InstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'installment_number' => $this->installment_number,
            'due_date' => $this->due_date?->toDateString(),
            'amount' => (float) $this->amount,
            'paid_amount' => (float) $this->paid_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'status' => $this->status,
            'paid_date' => $this->paid_date?->toDateString(),
            'notes' => $this->notes,
        ];
    }
}
