<?php

namespace App\Modules\Expenses\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Expenses\Models\Expense
 */
class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'description' => $this->description,
            'vendor_name' => $this->vendor_name,
            'vendor_display_name' => $this->vendorDisplayName(),
            'amount' => (float) $this->amount,
            'expense_date' => $this->expense_date?->toDateString(),
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'paid_on' => $this->paid_on?->toDateString(),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'status' => $this->status,
            'account' => $this->whenLoaded('account', fn () => [
                'uuid' => $this->account->uuid,
                'code' => $this->account->code,
                'name' => $this->account->name,
            ]),
            'property' => $this->whenLoaded('property', fn () => $this->property ? [
                'uuid' => $this->property->uuid,
                'name' => $this->property->name,
            ] : null),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'uuid' => $this->vendor->uuid,
                'name' => $this->vendor->name,
            ] : null),
            'recorded_by' => $this->whenLoaded('recordedBy', fn () => $this->recordedBy?->name),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
