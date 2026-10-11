<?php

namespace Tests\Feature\Invoices;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Repositories\EloquentInvoiceRepository;
use App\Modules\Invoices\Repositories\InvoiceRepositoryInterface;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvoiceService::class);
    }

    protected function makeLease(Company $company): Lease
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);

        return Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
    }

    protected function invoiceAttributes(Company $company, Lease $lease, array $overrides = []): array
    {
        return array_merge([
            'company_id' => $company->id,
            'lease_id' => $lease->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [
                ['description' => 'Rent', 'quantity' => 1, 'unit_price' => 500],
                ['description' => 'Water', 'quantity' => 2, 'unit_price' => 25],
            ],
        ], $overrides);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentInvoiceRepository::class, app(InvoiceRepositoryInterface::class));
    }

    public function test_create_generates_a_sequential_invoice_number_and_computes_the_total(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);

        $first = $this->service->create($this->invoiceAttributes($company, $lease));
        $second = $this->service->create($this->invoiceAttributes($company, $lease));

        $this->assertSame('INV-000001', $first->invoice_number);
        $this->assertSame('INV-000002', $second->invoice_number);
        $this->assertSame(550.0, (float) $first->total_amount);
        $this->assertCount(2, $first->items);
    }

    public function test_invoice_numbering_is_independent_per_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $invoiceA = $this->service->create($this->invoiceAttributes($companyA, $this->makeLease($companyA)));
        $invoiceB = $this->service->create($this->invoiceAttributes($companyB, $this->makeLease($companyB)));

        $this->assertSame('INV-000001', $invoiceA->invoice_number);
        $this->assertSame('INV-000001', $invoiceB->invoice_number);
    }

    public function test_paginate_filters_by_search_and_status(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);
        // A name the factory's random names can't contain ("Jane" collided with
        // e.g. "Janet", making this test flaky).
        $lease->tenant->update(['name' => 'Zqxwyv Testcase']);
        $invoice = $this->service->create($this->invoiceAttributes($company, $lease));
        $this->service->send($invoice);

        $otherLease = $this->makeLease($company);
        $this->service->create($this->invoiceAttributes($company, $otherLease));

        $this->assertSame(1, $this->service->paginate('Zqxwyv', null)->total());
        $this->assertSame(1, $this->service->paginate(null, 'sent')->total());
        $this->assertSame(2, $this->service->paginate(null, null)->total());
    }

    public function test_update_is_rejected_once_the_invoice_is_no_longer_draft(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);
        $invoice = $this->service->create($this->invoiceAttributes($company, $lease));
        $this->service->send($invoice);

        $this->expectException(ValidationException::class);

        $this->service->update($invoice, [
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'items' => [['description' => 'Rent', 'quantity' => 1, 'unit_price' => 999]],
        ]);
    }

    public function test_update_replaces_items_and_recomputes_the_total(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);
        $invoice = $this->service->create($this->invoiceAttributes($company, $lease));

        $updated = $this->service->update($invoice, [
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'items' => [['description' => 'Rent only', 'quantity' => 1, 'unit_price' => 700]],
        ]);

        $this->assertSame(700.0, (float) $updated->total_amount);
        $this->assertCount(1, $updated->items);
    }

    public function test_send_transitions_draft_to_sent(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->service->create($this->invoiceAttributes($company, $this->makeLease($company)));

        $this->service->send($invoice);

        $this->assertSame('sent', $invoice->fresh()->status);
    }

    public function test_send_rejects_an_already_sent_invoice(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->service->create($this->invoiceAttributes($company, $this->makeLease($company)));
        $this->service->send($invoice);

        $this->expectException(ValidationException::class);

        $this->service->send($invoice);
    }

    public function test_cancel_rejects_a_paid_invoice(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->service->create($this->invoiceAttributes($company, $this->makeLease($company)));
        $this->service->send($invoice);
        $this->service->recordPayment($invoice, ['amount' => (float) $invoice->total_amount, 'paid_on' => now()->toDateString(), 'method' => 'cash']);

        $this->expectException(ValidationException::class);

        $this->service->cancel($invoice);
    }

    public function test_record_payment_marks_partially_paid_then_paid(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->service->create($this->invoiceAttributes($company, $this->makeLease($company)));
        $this->service->send($invoice);

        $this->service->recordPayment($invoice, ['amount' => 200, 'paid_on' => now()->toDateString(), 'method' => 'cash']);
        $this->assertSame('partially_paid', $invoice->fresh()->status);

        $this->service->recordPayment($invoice, ['amount' => 350, 'paid_on' => now()->toDateString(), 'method' => 'bank_transfer']);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(550.0, (float) $invoice->fresh()->amount_paid);
    }

    public function test_record_payment_is_rejected_on_a_draft_invoice(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->service->create($this->invoiceAttributes($company, $this->makeLease($company)));

        $this->expectException(ValidationException::class);

        $this->service->recordPayment($invoice, ['amount' => 100, 'paid_on' => now()->toDateString(), 'method' => 'cash']);
    }

    public function test_delete_is_rejected_once_the_invoice_is_sent(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->service->create($this->invoiceAttributes($company, $this->makeLease($company)));
        $this->service->send($invoice);

        $this->expectException(ValidationException::class);

        $this->service->delete($invoice);
    }

    public function test_delete_succeeds_for_a_draft_invoice(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->service->create($this->invoiceAttributes($company, $this->makeLease($company)));

        $this->assertTrue($this->service->delete($invoice));
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }
}
