<?php

namespace Tests\Feature\Reports;

use App\Modules\Companies\Models\Company;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Reports\Services\RentRollReportService;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentRollReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_lists_every_unit_with_its_active_lease_and_falls_back_to_the_units_own_asking_rent_when_vacant(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id, 'name' => 'Riverside Apartments']);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'name' => 'Block A']);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Jane Tenant']);

        $occupiedUnit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => '101', 'status' => 'occupied', 'rent_amount' => 500]);
        Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $occupiedUnit->id, 'status' => 'active', 'rent_amount' => 550]);

        $vacantUnit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => '102', 'status' => 'vacant', 'rent_amount' => 480]);

        $rows = app(RentRollReportService::class)->generate();

        $this->assertCount(2, $rows);

        $occupiedRow = $rows->firstWhere('unit', '101');
        $this->assertSame('Riverside Apartments', $occupiedRow['property']);
        $this->assertSame('Jane Tenant', $occupiedRow['tenant']);
        $this->assertSame(550.0, $occupiedRow['rent_amount']);

        $vacantRow = $rows->firstWhere('unit', '102');
        $this->assertNull($vacantRow['tenant']);
        $this->assertSame(480.0, $vacantRow['rent_amount']);
    }

    public function test_generate_only_includes_the_current_companys_units(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id]);

        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);
        Unit::factory()->create(['company_id' => $otherCompany->id, 'building_id' => $otherBuilding->id]);

        app(\App\Core\Tenancy\CompanyContext::class)->set($company->id);

        $rows = app(RentRollReportService::class)->generate();

        $this->assertCount(1, $rows);
    }
}
