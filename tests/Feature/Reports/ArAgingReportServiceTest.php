<?php

namespace Tests\Feature\Reports;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Reports\Services\ArAgingReportService;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArAgingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeLease(Company $company): Lease
    {
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Jane Tenant']);

        return Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
    }

    public function test_generate_buckets_outstanding_invoices_by_how_overdue_they_are(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);

        Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'status' => 'sent', 'due_date' => now()->addDays(5), 'total_amount' => 100, 'amount_paid' => 0]);
        Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'status' => 'overdue', 'due_date' => now()->subDays(15), 'total_amount' => 200, 'amount_paid' => 0]);
        Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'status' => 'overdue', 'due_date' => now()->subDays(100), 'total_amount' => 300, 'amount_paid' => 0]);

        $result = app(ArAgingReportService::class)->generate();

        $this->assertCount(1, $result['buckets']['current']);
        $this->assertCount(1, $result['buckets']['1_30']);
        $this->assertCount(1, $result['buckets']['over_90']);
        $this->assertSame(600.0, $result['grand_total']);
    }

    public function test_generate_excludes_fully_paid_and_draft_invoices(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);

        Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'status' => 'paid', 'due_date' => now()->subDays(10), 'total_amount' => 100, 'amount_paid' => 100]);
        Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'status' => 'draft', 'due_date' => now()->subDays(10), 'total_amount' => 100, 'amount_paid' => 0]);

        $result = app(ArAgingReportService::class)->generate();

        $this->assertSame(0.0, $result['grand_total']);
    }

    public function test_generate_uses_the_remaining_balance_for_a_partially_paid_invoice(): void
    {
        $company = Company::factory()->create();
        $lease = $this->makeLease($company);

        Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'status' => 'partially_paid', 'due_date' => now()->subDays(5), 'total_amount' => 500, 'amount_paid' => 200]);

        $result = app(ArAgingReportService::class)->generate();

        $this->assertSame(300.0, $result['grand_total']);
    }
}
