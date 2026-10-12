<?php

namespace App\Modules\LandlordPortal\Services;

use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Services\TenantService;

/**
 * What a landlord can change themselves (portal and app): their own
 * properties, and tenants they add. Goes through the same services staff
 * use, so audit logs, usernames, the default phone-number password and
 * the welcome message all behave exactly the same.
 */
class LandlordManagementService
{
    public function __construct(
        protected PropertyService $properties,
        protected TenantService $tenants,
    ) {}

    public function createProperty(Landlord $landlord, array $data): Property
    {
        return $this->properties->create([
            ...$data,
            'status' => $data['status'] ?? 'active',
            'company_id' => $landlord->company_id,
            'landlord_id' => $landlord->id,
        ]);
    }

    public function updateProperty(Property $property, array $data): Property
    {
        // Never re-assigned to another landlord from here.
        unset($data['landlord_id'], $data['company_id']);

        return $this->properties->update($property, $data);
    }

    /** @return array{0: Tenant, 1: ?string} the tenant and their first password */
    public function createTenant(Landlord $landlord, array $data): array
    {
        $tenant = $this->tenants->create([
            ...$data,
            'status' => 'active',
            'company_id' => $landlord->company_id,
            'landlord_id' => $landlord->id,
        ]);

        return [$tenant, $this->tenants->issuedPassword()];
    }
}
