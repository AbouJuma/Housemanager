<?php

namespace App\Modules\LandlordPortal\Services;

use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Services\ExpenseService;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Properties\Services\BuildingService;
use App\Modules\Properties\Services\UnitService;
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
        protected BuildingService $buildings,
        protected UnitService $units,
        protected ExpenseService $expenses,
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

    public function createBuilding(Property $property, array $data): Building
    {
        return $this->buildings->create([
            ...$data,
            'status' => $data['status'] ?? 'active',
            'company_id' => $property->company_id,
            'property_id' => $property->id,
        ]);
    }

    public function createUnit(Building $building, array $data): Unit
    {
        return $this->units->create([
            ...$data,
            'status' => $data['status'] ?? 'vacant',
            'company_id' => $building->company_id,
            'building_id' => $building->id,
        ]);
    }

    public function updateUnit(Unit $unit, array $data): Unit
    {
        // A rented unit's status follows its lease, not the landlord.
        if ($unit->status === 'occupied') {
            unset($data['status']);
        }
        unset($data['building_id'], $data['company_id']);

        return $this->units->update($unit, $data);
    }

    public function createExpense(Landlord $landlord, array $data): Expense
    {
        return $this->expenses->create([
            ...$data,
            'company_id' => $landlord->company_id,
            'status' => 'active',
        ]);
    }

    /** Details only - amount, date and payment are already in the books. */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        return $this->expenses->update($expense, collect($data)->only(['property_id', 'vendor_name', 'description', 'reference', 'notes'])->all());
    }
}
