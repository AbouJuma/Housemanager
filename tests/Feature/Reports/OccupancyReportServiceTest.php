<?php

namespace Tests\Feature\Reports;

use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Reports\Services\OccupancyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccupancyReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_computes_occupancy_counts_and_rate_per_property(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id, 'name' => 'Riverside Apartments']);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant']);

        $rows = app(OccupancyReportService::class)->generate();

        $row = $rows->firstWhere('property', 'Riverside Apartments');
        $this->assertSame(3, $row['total_units']);
        $this->assertSame(2, $row['occupied']);
        $this->assertSame(1, $row['vacant']);
        $this->assertSame(66.7, $row['occupancy_rate']);
    }

    public function test_generate_handles_a_property_with_no_units(): void
    {
        $company = Company::factory()->create();
        Property::factory()->create(['company_id' => $company->id, 'name' => 'Empty Lot']);

        $rows = app(OccupancyReportService::class)->generate();

        $row = $rows->firstWhere('property', 'Empty Lot');
        $this->assertSame(0, $row['total_units']);
        $this->assertSame(0.0, $row['occupancy_rate']);
    }
}
