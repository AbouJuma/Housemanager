<?php

namespace Tests\Feature\Tenants;

use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Repositories\EloquentTenantRepository;
use App\Modules\Tenants\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TenantService::class);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentTenantRepository::class, app(TenantRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_type_and_status(): void
    {
        $company = Company::factory()->create();
        Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Jane Doe', 'type' => 'individual', 'status' => 'active']);
        Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Acme Corp', 'type' => 'company', 'status' => 'active']);
        Tenant::factory()->blacklisted()->create(['company_id' => $company->id, 'name' => 'Bad Tenant', 'type' => 'individual']);

        $this->assertSame(1, $this->service->paginate('Jane', null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, 'company', null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'blacklisted')->total());
        $this->assertSame(3, $this->service->paginate(null, null, null)->total());
    }

    public function test_create_stores_a_tenant_for_the_given_company(): void
    {
        $company = Company::factory()->create();

        $tenant = $this->service->create([
            'company_id' => $company->id,
            'name' => 'Jane Doe',
            'type' => 'individual',
            'identification_type' => 'national_id',
            'identification_number' => 'ID-12345678',
        ]);

        $this->assertSame('Jane Doe', $tenant->name);
        $this->assertSame('ID-12345678', $tenant->identification_number);
    }
}
