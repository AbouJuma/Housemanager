<?php

namespace Tests\Feature\Properties;

use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Properties\Repositories\PropertyRepositoryInterface;
use App\Modules\Properties\Repositories\EloquentPropertyRepository;
use App\Modules\Properties\Services\PropertyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PropertyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PropertyService::class);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentPropertyRepository::class, app(PropertyRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_type_and_status(): void
    {
        $company = Company::factory()->create();
        Property::factory()->create(['company_id' => $company->id, 'name' => 'Sunset Apartments', 'type' => 'residential', 'status' => 'active']);
        Property::factory()->create(['company_id' => $company->id, 'name' => 'Industrial Park', 'type' => 'industrial', 'status' => 'active']);
        Property::factory()->create(['company_id' => $company->id, 'name' => 'Old Warehouse', 'type' => 'warehouse', 'status' => 'archived']);

        $this->assertSame(1, $this->service->paginate('Sunset', null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, 'industrial', null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'archived')->total());
        $this->assertSame(3, $this->service->paginate(null, null, null)->total());
    }

    public function test_paginate_includes_building_and_unit_counts(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        Unit::factory()->count(2)->create(['company_id' => $company->id, 'building_id' => $building->id]);

        $result = $this->service->paginate(null, null, null)->first();

        $this->assertSame(1, $result->buildings_count);
        $this->assertSame(2, $result->units_count);
    }
}
