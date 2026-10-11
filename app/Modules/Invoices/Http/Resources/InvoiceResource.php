<?php

namespace App\Modules\Invoices\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Invoices\Models\Invoice
 */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'balance_due' => $this->balanceDue(),
            'notes' => $this->notes,
            'lease' => $this->whenLoaded('lease', fn () => $this->lease ? [
                'uuid' => $this->lease->uuid,
                'tenant' => $this->lease->relationLoaded('tenant') ? [
                    'uuid' => $this->lease->tenant->uuid,
                    'name' => $this->lease->tenant->name,
                ] : null,
                'unit' => $this->lease->relationLoaded('unit') ? [
                    'uuid' => $this->lease->unit->uuid,
                    'unit_number' => $this->lease->unit->unit_number,
                ] : null,
            ] : null),
            // Added alongside the buyer mobile portal - a sale-linked
            // invoice has no `lease`, only `sale` (Invoice belongs to
            // exactly one of the two, see the model's own lease_id/
            // sale_id columns).
            'sale' => $this->whenLoaded('sale', fn () => $this->sale ? [
                'uuid' => $this->sale->uuid,
                'unit' => $this->sale->relationLoaded('unit') ? [
                    'uuid' => $this->sale->unit->uuid,
                    'unit_number' => $this->sale->unit->unit_number,
                    'building' => $this->sale->unit->relationLoaded('building') ? [
                        'name' => $this->sale->unit->building->name,
                        'property' => $this->sale->unit->building->relationLoaded('property') ? [
                            'name' => $this->sale->unit->building->property->name,
                        ] : null,
                    ] : null,
                ] : null,
            ] : null),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => InvoicePaymentResource::collection($this->whenLoaded('payments')),
            // Plain inline mapping, not a dedicated Resource class - a
            // payment attempt is RentCollection's own model
            // (ARCHITECTURE.md §10f), and this is the one place
            // Invoices' own API surface exposes it, not worth a
            // full cross-module Resource class for four fields.
            'payment_attempts' => $this->whenLoaded('paymentAttempts', fn () => $this->paymentAttempts->map(fn ($attempt) => [
                'uuid' => $attempt->uuid,
                'provider' => $attempt->provider,
                'amount' => $attempt->amountMajor(),
                'status' => $attempt->status,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
