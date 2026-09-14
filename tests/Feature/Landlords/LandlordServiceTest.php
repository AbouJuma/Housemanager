<?php

namespace Tests\Feature\Landlords;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Landlords\Repositories\EloquentLandlordRepository;
use App\Modules\Landlords\Repositories\LandlordRepositoryInterface;
use App\Modules\Landlords\Services\LandlordService;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandlordServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LandlordService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LandlordService::class);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentLandlordRepository::class, app(LandlordRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_type_and_status(): void
    {
        $company = Company::factory()->create();
        Landlord::factory()->create(['company_id' => $company->id, 'name' => 'Jane Doe', 'type' => 'individual', 'status' => 'active']);
        Landlord::factory()->create(['company_id' => $company->id, 'name' => 'Acme Holdings', 'type' => 'company', 'status' => 'active']);
        Landlord::factory()->inactive()->create(['company_id' => $company->id, 'name' => 'Old Landlord', 'type' => 'individual']);

        $this->assertSame(1, $this->service->paginate('Jane', null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, 'company', null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'inactive')->total());
        $this->assertSame(3, $this->service->paginate(null, null, null)->total());
    }

    public function test_paginate_includes_property_counts(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        Property::factory()->count(2)->create(['company_id' => $company->id, 'landlord_id' => $landlord->id]);

        $result = $this->service->paginate(null, null, null)->first();

        $this->assertSame(2, $result->properties_count);
    }

    public function test_create_assembles_individual_bank_fields_into_the_bank_details_array(): void
    {
        $company = Company::factory()->create();

        $landlord = $this->service->create([
            'company_id' => $company->id,
            'name' => 'Jane Doe',
            'type' => 'individual',
            'bank_name' => 'CRDB Bank',
            'bank_account_number' => '1234567890',
            'bank_account_name' => '',
            'bank_branch' => null,
        ]);

        $this->assertSame('CRDB Bank', $landlord->bankDetail('name'));
        $this->assertSame('1234567890', $landlord->bankDetail('account_number'));
        $this->assertNull($landlord->bankDetail('account_name'));
    }

    public function test_create_leaves_bank_details_null_when_no_bank_fields_are_given(): void
    {
        $company = Company::factory()->create();

        $landlord = $this->service->create(['company_id' => $company->id, 'name' => 'Jane Doe', 'type' => 'individual']);

        $this->assertNull($landlord->bank_details);
    }

    public function test_update_reassembles_bank_details_from_the_given_fields(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create([
            'company_id' => $company->id,
            'bank_details' => ['name' => 'Old Bank', 'account_number' => 'old-number'],
        ]);

        $updated = $this->service->update($landlord, [
            'name' => $landlord->name,
            'type' => $landlord->type,
            'status' => 'active',
            'bank_name' => 'New Bank',
            'bank_account_number' => 'new-number',
        ]);

        $this->assertSame('New Bank', $updated->bankDetail('name'));
        $this->assertSame('new-number', $updated->bankDetail('account_number'));
    }
}
