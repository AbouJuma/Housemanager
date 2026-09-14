<?php

namespace Tests\Feature\Properties;

use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnitModelTest extends TestCase
{
    use RefreshDatabase;

    protected function makeBuilding(): Building
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);

        return Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
    }

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $building = $this->makeBuilding();
        $unit = Unit::factory()->create(['company_id' => $building->company_id, 'building_id' => $building->id]);

        $this->assertTrue(Str::isUuid($unit->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Unit())->getRouteKeyName());
    }

    public function test_building_relation(): void
    {
        $building = $this->makeBuilding();
        $unit = Unit::factory()->create(['company_id' => $building->company_id, 'building_id' => $building->id]);

        $this->assertTrue($unit->building->is($building));
    }

    public function test_is_vacant_reflects_status(): void
    {
        $building = $this->makeBuilding();
        $vacant = Unit::factory()->vacant()->create(['company_id' => $building->company_id, 'building_id' => $building->id]);
        $occupied = Unit::factory()->occupied()->create(['company_id' => $building->company_id, 'building_id' => $building->id]);

        $this->assertTrue($vacant->isVacant());
        $this->assertFalse($occupied->isVacant());
    }

    public function test_unit_number_is_unique_within_a_building(): void
    {
        $building = $this->makeBuilding();
        Unit::factory()->create(['company_id' => $building->company_id, 'building_id' => $building->id, 'unit_number' => 'A101']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Unit::factory()->create(['company_id' => $building->company_id, 'building_id' => $building->id, 'unit_number' => 'A101']);
    }
}
