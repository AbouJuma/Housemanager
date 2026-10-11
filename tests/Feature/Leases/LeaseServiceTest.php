<?php

namespace Tests\Feature\Leases;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Notifications\LandlordPaymentConfirmation;
use App\Modules\Invoices\Notifications\TenantPaymentConfirmation;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Notifications\LeaseCreatedLandlordNotice;
use App\Modules\Leases\Repositories\EloquentLeaseRepository;
use App\Modules\Leases\Repositories\LeaseRepositoryInterface;
use App\Modules\Leases\Services\LeaseService;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    /**
     * @return array{Company, Tenant, Landlord, Unit}
     */
    protected function makeLeaseScenario(): array
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant']);

        return [$company, $tenant, $landlord, $unit];
    }

    protected function createLease(Company $company, Tenant $tenant, Unit $unit, float $rent, float $deposit): Lease
    {
        return $this->service->create([
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => $rent,
            'deposit_amount' => $deposit,
        ]);
    }

    /**
     * A payment message only makes sense if money actually changed hands
     * at signing: no deposit means no payment is recorded and neither
     * the tenant nor the landlord gets a payment confirmation.
     */
    public function test_create_without_a_deposit_records_no_payment_and_sends_no_payment_messages(): void
    {
        Notification::fake();
        [$company, $tenant, $landlord, $unit] = $this->makeLeaseScenario();

        $lease = $this->createLease($company, $tenant, $unit, 500, 0);

        $invoice = $lease->invoices()->first();
        $this->assertEquals(0, $invoice->amount_paid);
        $this->assertSame('sent', $invoice->status);
        Notification::assertSentTo($landlord, LeaseCreatedLandlordNotice::class);
        Notification::assertNotSentTo($landlord, LandlordPaymentConfirmation::class);
        Notification::assertNotSentTo($tenant, TenantPaymentConfirmation::class);
    }

    /**
     * The deposit is applied to the first invoice so the due amount
     * reflects it (200,000 rent - 150,000 deposit = 50,000 still due),
     * and both parties are told through the payment templates.
     */
    public function test_create_with_a_deposit_applies_it_to_the_first_invoice_and_notifies_both_parties(): void
    {
        Notification::fake();
        [$company, $tenant, $landlord, $unit] = $this->makeLeaseScenario();

        $lease = $this->createLease($company, $tenant, $unit, 200000, 150000);

        $invoice = $lease->invoices()->first();
        $this->assertEquals(150000, $invoice->amount_paid);
        $this->assertEquals(50000, $invoice->balanceDue());
        $this->assertSame('partially_paid', $invoice->status);
        Notification::assertSentTo($landlord, LandlordPaymentConfirmation::class);
        Notification::assertSentTo($tenant, TenantPaymentConfirmation::class);
    }

    public function test_a_deposit_larger_than_the_first_invoice_is_capped_and_marks_it_paid(): void
    {
        Notification::fake();
        [$company, $tenant, $landlord, $unit] = $this->makeLeaseScenario();

        $lease = $this->createLease($company, $tenant, $unit, 200000, 500000);

        $invoice = $lease->invoices()->first();
        $this->assertEquals(200000, $invoice->amount_paid);
        $this->assertSame('paid', $invoice->status);
    }
}
