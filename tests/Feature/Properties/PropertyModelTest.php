<?php

namespace Tests\Feature\Properties;

use App\Core\Tenancy\CompanyContext;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PropertyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $property = Property::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertTrue(Str::isUuid($property->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Property())->getRouteKeyName());
    }

    public function test_buildings_and_units_relations_traverse_the_hierarchy(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        Unit::factory()->count(3)->create(['company_id' => $company->id, 'building_id' => $building->id]);

        $this->assertCount(1, $property->buildings);
        $this->assertCount(3, $property->units);
    }

    public function test_cover_photo_url_is_null_without_media(): void
    {
        $property = Property::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertNull($property->coverPhotoUrl());
    }

    public function test_a_property_from_another_company_is_not_visible_under_the_current_company_context(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        Property::factory()->create(['company_id' => $otherCompany->id]);
        $mine = Property::factory()->create(['company_id' => $ownCompany->id]);

        app(CompanyContext::class)->set($ownCompany->id);

        $this->assertSame(1, Property::count());
        $this->assertTrue(Property::first()->is($mine));

        app(CompanyContext::class)->clear();
    }
}
