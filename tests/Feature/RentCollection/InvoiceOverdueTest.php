<?php

namespace Tests\Feature\RentCollection;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\RentCollection\Notifications\InvoiceOverdue;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceOverdueTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInvoice(): Invoice
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Jane Doe']);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        return Invoice::factory()->overdue()->create([
            'company_id' => $company->id,
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-000002',
            'total_amount' => 500,
        ]);
    }

    public function test_via_includes_database_mail_and_sms(): void
    {
        $invoice = $this->makeInvoice();

        $this->assertSame(['database', 'company-mail', 'company-sms'], (new InvoiceOverdue($invoice))->via(null));
    }

    public function test_to_array_contains_the_invoice_id_for_deduplication(): void
    {
        $invoice = $this->makeInvoice();

        $data = (new InvoiceOverdue($invoice))->toArray(null);

        $this->assertSame($invoice->id, $data['invoice_id']);
        $this->assertStringContainsString('overdue', $data['message']);
    }

    public function test_to_company_mail_addresses_the_tenant_by_name(): void
    {
        $invoice = $this->makeInvoice();
        $tenant = $invoice->lease->tenant;

        $mail = (new InvoiceOverdue($invoice))->toCompanyMail($tenant);

        $this->assertStringContainsString($tenant->name, $mail['body']);
        $this->assertStringContainsString('INV-000002', $mail['subject']);
    }
}
