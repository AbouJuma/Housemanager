<?php

namespace Tests\Feature\Leases;

use App\Modules\Companies\Models\Company;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeaseModelTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUnit(Company $company): Unit
    {
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        return Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id]);
    }

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company);

        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        $this->assertTrue(Str::isUuid($lease->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Lease())->getRouteKeyName());
    }

    public function test_is_active_reflects_status(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company);

        $active = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
        $terminated = Lease::factory()->terminated()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $this->makeUnit($company)->id]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($terminated->isActive());
    }

    public function test_it_belongs_to_a_tenant_and_a_unit(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company);

        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        $this->assertTrue($lease->tenant->is($tenant));
        $this->assertTrue($lease->unit->is($unit));
    }
}
