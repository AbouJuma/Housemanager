<?php

namespace App\Modules\Landlords\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Landlords\Models\Landlord
 */
class LandlordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'type' => $this->type,
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
            // Legitimately viewable by an authorized company admin (this
            // is the company's own reference data about its landlord,
            // not a write-only third-party secret like Integration's
            // credentials - see the bank_details column's migration
            // comment) - decrypted in full, not masked.
            'bank_details' => [
                'bank_name' => $this->bankDetail('name'),
                'account_name' => $this->bankDetail('account_name'),
                'account_number' => $this->bankDetail('account_number'),
                'branch' => $this->bankDetail('branch'),
                'swift_code' => $this->bankDetail('swift_code'),
            ],
            'notes' => $this->notes,
            'photo_url' => $this->photoUrl(),
            'properties_count' => $this->whenCounted('properties'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
