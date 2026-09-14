<?php

namespace Tests\Feature\Vendors;

use App\Modules\Companies\Models\Company;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\Vendors\Repositories\EloquentVendorRepository;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use App\Modules\Vendors\Services\VendorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VendorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(VendorService::class);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentVendorRepository::class, app(VendorRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_category_and_status(): void
    {
        $company = Company::factory()->create();
        Vendor::factory()->create(['company_id' => $company->id, 'name' => 'Ace Plumbing', 'category' => 'plumbing', 'status' => 'active']);
        Vendor::factory()->create(['company_id' => $company->id, 'name' => 'Bright Electric', 'category' => 'electrical', 'status' => 'active']);
        Vendor::factory()->inactive()->create(['company_id' => $company->id, 'name' => 'Old Cleaners', 'category' => 'cleaning']);

        $this->assertSame(1, $this->service->paginate('Plumbing', null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, 'electrical', null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'inactive')->total());
        $this->assertSame(3, $this->service->paginate(null, null, null)->total());
    }

    public function test_create_assembles_flat_bank_fields_into_bank_details(): void
    {
        $company = Company::factory()->create();

        $vendor = $this->service->create([
            'company_id' => $company->id,
            'name' => 'Ace Plumbing',
            'type' => 'company',
            'category' => 'plumbing',
            'bank_name' => 'CRDB Bank',
            'bank_account_name' => 'Ace Plumbing Ltd',
            'bank_account_number' => '1234567890',
            'bank_branch' => 'Dar es Salaam',
            'bank_swift_code' => 'CORUTZTZ',
        ]);

        $this->assertSame('CRDB Bank', $vendor->bankDetail('name'));
        $this->assertSame('1234567890', $vendor->bankDetail('account_number'));
    }

    public function test_create_leaves_bank_details_null_when_none_provided(): void
    {
        $company = Company::factory()->create();

        $vendor = $this->service->create([
            'company_id' => $company->id,
            'name' => 'Ace Plumbing',
            'type' => 'company',
            'category' => 'plumbing',
        ]);

        $this->assertNull($vendor->bank_details);
    }
}
