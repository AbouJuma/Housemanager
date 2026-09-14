<?php

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration test across module boundaries: InvoiceService::send()
 * (Invoices module) dispatches InvoiceSent, which
 * PostInvoiceRevenueJournalEntry (Accounting module) listens for -
 * this exercises the real event dispatch, not just the listener
 * in isolation, since the wiring itself (event registered against the
 * right listener in AccountingServiceProvider) is exactly what could
 * silently break.
 */
class PostInvoiceRevenueJournalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInvoice(Company $company, float $total = 500): Invoice
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        return Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'total_amount' => $total]);
    }

    public function test_sending_an_invoice_posts_a_balanced_revenue_journal_entry(): void
    {
        $company = Company::factory()->create();
        $receivable = Account::factory()->create(['company_id' => $company->id, 'code' => '1200', 'type' => 'asset']);
        $income = Account::factory()->create(['company_id' => $company->id, 'code' => '4000', 'type' => 'income']);
        $invoice = $this->makeInvoice($company, 500);

        app(InvoiceService::class)->send($invoice);

        $entry = JournalEntry::query()->where('company_id', $company->id)->where('source_id', $invoice->id)->first();

        $this->assertNotNull($entry);
        $this->assertSame('posted', $entry->status);
        $this->assertSame(500.0, $receivable->fresh()->balance());
        $this->assertSame(500.0, $income->fresh()->balance());
    }

    public function test_sending_an_invoice_without_default_accounts_configured_does_not_fail_the_send(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->makeInvoice($company, 500);

        $sent = app(InvoiceService::class)->send($invoice);

        $this->assertSame('sent', $sent->status);
        $this->assertSame(0, JournalEntry::query()->where('company_id', $company->id)->count());
    }
}
