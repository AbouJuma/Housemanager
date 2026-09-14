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

class PostInvoicePaymentJournalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function makeDraftInvoice(Company $company, float $total = 500): Invoice
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        return Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'total_amount' => $total]);
    }

    public function test_recording_a_cash_payment_debits_cash_and_credits_receivable(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id, 'code' => '1000', 'type' => 'asset']);
        $receivable = Account::factory()->create(['company_id' => $company->id, 'code' => '1200', 'type' => 'asset']);
        Account::factory()->create(['company_id' => $company->id, 'code' => '4000', 'type' => 'income']);
        $invoice = $this->makeDraftInvoice($company, 500);

        // send() first, so the revenue entry (debiting Receivable) is
        // the one recordPayment()'s cash-receipt entry (crediting
        // Receivable) is actually netting against - a payment recorded
        // against an invoice that was never sent isn't a real scenario
        // this app allows anyway (recordPayment() itself requires a
        // sent invoice).
        app(InvoiceService::class)->send($invoice);
        app(InvoiceService::class)->recordPayment($invoice, ['amount' => 500, 'paid_on' => now()->toDateString(), 'method' => 'cash']);

        $this->assertSame(500.0, $cash->fresh()->balance());
        $this->assertSame(0.0, $receivable->fresh()->balance());
    }

    public function test_recording_a_bank_transfer_payment_debits_bank_not_cash(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id, 'code' => '1000', 'type' => 'asset']);
        $bank = Account::factory()->create(['company_id' => $company->id, 'code' => '1100', 'type' => 'asset']);
        Account::factory()->create(['company_id' => $company->id, 'code' => '1200', 'type' => 'asset']);
        Account::factory()->create(['company_id' => $company->id, 'code' => '4000', 'type' => 'income']);
        $invoice = $this->makeDraftInvoice($company, 500);
        app(InvoiceService::class)->send($invoice);

        app(InvoiceService::class)->recordPayment($invoice, ['amount' => 500, 'paid_on' => now()->toDateString(), 'method' => 'bank_transfer']);

        $this->assertSame(0.0, $cash->fresh()->balance());
        $this->assertSame(500.0, $bank->fresh()->balance());
    }

    public function test_recording_a_payment_without_default_accounts_configured_does_not_fail_the_payment(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->makeDraftInvoice($company, 500);
        app(InvoiceService::class)->send($invoice);

        $payment = app(InvoiceService::class)->recordPayment($invoice, ['amount' => 500, 'paid_on' => now()->toDateString(), 'method' => 'cash']);

        $this->assertSame(500.0, (float) $payment->amount);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0, JournalEntry::query()->where('company_id', $company->id)->count());
    }
}
