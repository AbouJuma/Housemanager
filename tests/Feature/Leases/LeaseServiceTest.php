<?php

namespace Tests\Feature\Leases;

use App\Modules\Companies\Models\Company;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Repositories\EloquentLeaseRepository;
use App\Modules\Leases\Repositories\LeaseRepositoryInterface;
use App\Modules\Leases\Services\LeaseService;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeaseService::class);
    }

    protected function makeUnit(Company $company, string $status = 'vacant'): Unit
    {
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        return Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => $status]);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentLeaseRepository::class, app(LeaseRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_and_status(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Jane Doe']);
        $unit = $this->makeUnit($company, 'occupied');
        Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
        Lease::factory()->terminated()->create([
            'company_id' => $company->id,
            'tenant_id' => Tenant::factory()->create(['company_id' => $company->id])->id,
            'unit_id' => $this->makeUnit($company)->id,
        ]);

        $this->assertSame(1, $this->service->paginate('Jane', null)->total());
        $this->assertSame(1, $this->service->paginate(null, 'terminated')->total());
        $this->assertSame(2, $this->service->paginate(null, null)->total());
    }

    public function test_create_marks_the_unit_as_occupied(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company);

        $this->service->create([
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => 500,
        ]);

        $this->assertSame('occupied', $unit->fresh()->status);
    }

    public function test_updating_status_to_terminated_sets_terminated_at_and_frees_the_unit(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company, 'occupied');
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        $updated = $this->service->update($lease, [
            'end_date' => $lease->end_date->toDateString(),
            'billing_cycle' => $lease->billing_cycle,
            'rent_amount' => $lease->rent_amount,
            'status' => 'terminated',
            'termination_reason' => 'Tenant moved out early.',
        ]);

        $this->assertSame('terminated', $updated->status);
        $this->assertNotNull($updated->terminated_at);
        $this->assertSame('vacant', $unit->fresh()->status);
    }

    public function test_updating_status_to_expired_frees_the_unit(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company, 'occupied');
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        $this->service->update($lease, [
            'end_date' => $lease->end_date->toDateString(),
            'billing_cycle' => $lease->billing_cycle,
            'rent_amount' => $lease->rent_amount,
            'status' => 'expired',
        ]);

        $this->assertSame('vacant', $unit->fresh()->status);
    }
}
