<?php

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Inventory\Models\InventoryItem
 */
class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'sku' => $this->sku,
            'category' => $this->category,
            'unit_of_measure' => $this->unit_of_measure,
            'quantity_on_hand' => (float) $this->quantity_on_hand,
            'reorder_level' => $this->reorder_level !== null ? (float) $this->reorder_level : null,
            'unit_cost' => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'is_low_stock' => $this->isLowStock(),
            'status' => $this->status,
            'notes' => $this->notes,
            'property' => $this->whenLoaded('property', fn () => $this->property ? [
                'uuid' => $this->property->uuid,
                'name' => $this->property->name,
            ] : null),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'uuid' => $this->vendor->uuid,
                'name' => $this->vendor->name,
            ] : null),
            'transactions_count' => $this->whenCounted('transactions'),
            'recent_transactions' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($transaction) => [
                'uuid' => $transaction->uuid,
                'type' => $transaction->type,
                'quantity' => (float) $transaction->quantity,
                'quantity_after' => (float) $transaction->quantity_after,
                'transaction_date' => $transaction->transaction_date->toDateString(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
