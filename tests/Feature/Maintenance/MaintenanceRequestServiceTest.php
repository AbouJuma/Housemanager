<?php

namespace Tests\Feature\Maintenance;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Maintenance\Repositories\EloquentMaintenanceRequestRepository;
use App\Modules\Maintenance\Repositories\MaintenanceRequestRepositoryInterface;
use App\Modules\Maintenance\Services\MaintenanceRequestService;
use App\Modules\Properties\Models\Property;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MaintenanceRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MaintenanceRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MaintenanceRequestService::class);
    }

    protected function makeRequest(Company $company, array $overrides = []): MaintenanceRequest
    {
        $property = Property::factory()->create(['company_id' => $company->id]);

        return MaintenanceRequest::factory()->create(array_merge([
            'company_id' => $company->id,
            'property_id' => $property->id,
        ], $overrides));
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentMaintenanceRequestRepository::class, app(MaintenanceRequestRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_property_status_and_priority(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $this->makeRequest($company, ['property_id' => $property->id, 'title' => 'Leaking faucet', 'priority' => 'high']);
        $this->makeRequest($company, ['title' => 'Broken AC unit', 'status' => 'completed', 'completed_date' => now()->toDateString(), 'priority' => 'low']);

        $this->assertSame(1, $this->service->paginate('Leaking', null, null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, $property->id, null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'completed', null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, null, 'high')->total());
        $this->assertSame(2, $this->service->paginate(null, null, null, null)->total());
    }

    public function test_update_auto_sets_completed_date_when_status_transitions_to_completed(): void
    {
        $company = Company::factory()->create();
        $request = $this->makeRequest($company);

        $updated = $this->service->update($request, [
            'property_id' => $request->property_id,
            'title' => $request->title,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'completed',
            'reported_date' => $request->reported_date->toDateString(),
        ]);

        $this->assertNotNull($updated->completed_date);
        $this->assertSame(now()->toDateString(), $updated->completed_date->toDateString());
    }

    public function test_update_does_not_override_an_explicitly_provided_completed_date(): void
    {
        $company = Company::factory()->create();
        $request = $this->makeRequest($company);
        $explicitDate = now()->subDays(3)->toDateString();

        $updated = $this->service->update($request, [
            'property_id' => $request->property_id,
            'title' => $request->title,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'completed',
            'completed_date' => $explicitDate,
            'reported_date' => $request->reported_date->toDateString(),
        ]);

        $this->assertSame($explicitDate, $updated->completed_date->toDateString());
    }

    public function test_record_expense_creates_a_linked_expense_and_updates_actual_cost(): void
    {
        $company = Company::factory()->create();
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $request = $this->makeRequest($company, ['vendor_id' => $vendor->id]);

        $updated = $this->service->recordExpense($request, [
            'account_id' => $account->id,
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $this->assertNotNull($updated->expense_id);
        $this->assertSame(150.0, (float) $updated->actual_cost);
        $this->assertTrue($updated->hasRecordedExpense());
        $this->assertDatabaseHas('expenses', ['id' => $updated->expense_id, 'vendor_id' => $vendor->id, 'property_id' => $request->property_id]);
    }

    public function test_record_expense_rejects_a_request_that_already_has_one(): void
    {
        $company = Company::factory()->create();
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $request = $this->makeRequest($company);

        $this->service->recordExpense($request, [
            'account_id' => $account->id,
            'amount' => 100,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->recordExpense($request->fresh(), [
            'account_id' => $account->id,
            'amount' => 50,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);
    }
}
