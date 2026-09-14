<?php

namespace Tests\Feature\Invoices;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    protected function makeLease(Company $company): Lease
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);

        return Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
    }

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);

        $invoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id]);

        $this->assertTrue(Str::isUuid($invoice->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Invoice())->getRouteKeyName());
    }

    public function test_balance_due_is_total_minus_paid(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'lease_id' => $lease->id,
            'total_amount' => 500,
            'amount_paid' => 200,
        ]);

        $this->assertSame(300.0, $invoice->balanceDue());
    }

    public function test_is_editable_only_while_draft(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);

        $draft = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id]);
        $sent = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $lease->id]);

        $this->assertTrue($draft->isEditable());
        $this->assertFalse($sent->isEditable());
    }

    public function test_it_belongs_to_a_lease(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);

        $invoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id]);

        $this->assertTrue($invoice->lease->is($lease));
    }
}
