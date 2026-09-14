<?php

namespace App\Modules\Tenants\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tenants\Models\Tenant
 */
class TenantResource extends JsonResource
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
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'identification_type' => $this->identification_type,
            // Legitimately viewable by an authorized company admin -
            // the company's own tenant record, needed in full for
            // identity verification/lease documents, not a write-only
            // third-party secret. Same reasoning as Landlord's
            // bank_details (ARCHITECTURE.md §10b).
            'identification_number' => $this->identification_number,
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country_code' => $this->country_code,
            ],
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
            ],
            'notes' => $this->notes,
            'photo_url' => $this->photoUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
