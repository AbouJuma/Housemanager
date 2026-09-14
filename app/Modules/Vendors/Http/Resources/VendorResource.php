<?php

namespace App\Modules\Vendors\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Vendors\Models\Vendor
 */
class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'status' => $this->status,
            'email' => $this->email,
            'phone' => $this->phone,
            'secondary_phone' => $this->secondary_phone,
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country_code' => $this->country_code,
            ],
            'tax_id' => $this->tax_id,
            // Legitimately viewable by an authorized company admin -
            // the company's own reference data about who it pays, not
            // a write-only third-party secret - decrypted in full, not
            // masked. Same reasoning as Landlord's bank_details.
            'bank_details' => [
                'bank_name' => $this->bankDetail('name'),
                'account_name' => $this->bankDetail('account_name'),
                'account_number' => $this->bankDetail('account_number'),
                'branch' => $this->bankDetail('branch'),
                'swift_code' => $this->bankDetail('swift_code'),
            ],
            'notes' => $this->notes,
            'photo_url' => $this->photoUrl(),
            'expenses_count' => $this->whenCounted('expenses'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
