<?php

namespace Tests\Feature\RentCollection;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use App\Modules\RentCollection\Services\RentCollectionService;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RentCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RentCollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RentCollectionService::class);
    }

    protected function makeLease(Company $company): Lease
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);

        return Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
    }

    protected function makeSentInvoice(Company $company, float $total = 500): Invoice
    {
        return Invoice::factory()->sent()->create([
            'company_id' => $company->id,
            'lease_id' => $this->makeLease($company)->id,
            'total_amount' => $total,
            'amount_paid' => 0,
        ]);
    }

    protected function activateStripe(Company $company): Integration
    {
        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'is_active' => true,
            'credentials' => ['api_secret' => 'sk_test_xxx'],
        ]);
    }

    public function test_charge_invoice_rejects_a_draft_invoice(): void
    {
        $company = Company::factory()->create();
        $this->activateStripe($company);
        $invoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $this->expectException(ValidationException::class);

        $this->service->chargeInvoice($invoice, 100);
    }

    public function test_charge_invoice_rejects_an_amount_above_the_balance_due(): void
    {
        $company = Company::factory()->create();
        $this->activateStripe($company);
        $invoice = $this->makeSentInvoice($company, 500);

        $this->expectException(ValidationException::class);

        $this->service->chargeInvoice($invoice, 500.01);
    }

    public function test_charge_invoice_rejects_when_no_active_payment_provider_is_configured(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->makeSentInvoice($company);

        $this->expectException(ValidationException::class);

        $this->service->chargeInvoice($invoice, 100);
    }

    public function test_charge_invoice_creates_a_pending_attempt_on_a_redirect_based_gateway(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/pay/cs_1'], 200)]);

        $company = Company::factory()->create();
        $this->activateStripe($company);
        $invoice = $this->makeSentInvoice($company, 500);

        $attempt = $this->service->chargeInvoice($invoice, 500);

        $this->assertSame('pending', $attempt->status);
        $this->assertSame('stripe', $attempt->provider);
        $this->assertSame('cs_1', $attempt->reference);
        $this->assertSame(50000, $attempt->amount_minor);
    }

    public function test_charge_invoice_creates_a_failed_attempt_when_the_gateway_rejects_the_request(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'Invalid API key.']], 401)]);

        $company = Company::factory()->create();
        $this->activateStripe($company);
        $invoice = $this->makeSentInvoice($company, 500);

        $attempt = $this->service->chargeInvoice($invoice, 500);

        $this->assertSame('failed', $attempt->status);
    }

    public function test_verify_attempt_marks_successful_and_records_the_payment(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'paid'], 200)]);

        $company = Company::factory()->create();
        $this->activateStripe($company);
        $invoice = $this->makeSentInvoice($company, 500);
        $attempt = InvoicePaymentAttempt::factory()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'provider' => 'stripe',
            'reference' => 'cs_1',
            'amount_minor' => 50000,
        ]);

        $verified = $this->service->verifyAttempt($attempt);

        $this->assertSame('successful', $verified->status);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(500.0, (float) $invoice->fresh()->amount_paid);
        $this->assertDatabaseHas('invoice_payments', ['invoice_id' => $invoice->id, 'method' => 'gateway', 'reference' => 'cs_1']);
    }

    public function test_verify_attempt_leaves_it_pending_when_the_gateway_has_not_confirmed_yet(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'unpaid'], 200)]);

        $company = Company::factory()->create();
        $this->activateStripe($company);
        $invoice = $this->makeSentInvoice($company, 500);
        $attempt = InvoicePaymentAttempt::factory()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'provider' => 'stripe',
            'reference' => 'cs_1',
            'amount_minor' => 50000,
        ]);

        $verified = $this->service->verifyAttempt($attempt);

        $this->assertSame('pending', $verified->status);
        $this->assertNotNull($verified->verified_at);
        $this->assertSame('sent', $invoice->fresh()->status);
    }

    public function test_verify_attempt_is_a_no_op_on_an_already_resolved_attempt(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->makeSentInvoice($company);
        $attempt = InvoicePaymentAttempt::factory()->successful()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
        ]);

        Http::fake();

        $this->service->verifyAttempt($attempt);

        Http::assertNothingSent();
    }

    public function test_expire_stale_attempts_fails_only_attempts_older_than_a_day(): void
    {
        $company = Company::factory()->create();
        $invoice = $this->makeSentInvoice($company);

        $old = InvoicePaymentAttempt::factory()->create(['company_id' => $company->id, 'invoice_id' => $invoice->id]);
        $old->forceFill(['created_at' => now()->subDays(2)])->save();

        $recent = InvoicePaymentAttempt::factory()->create(['company_id' => $company->id, 'invoice_id' => $invoice->id]);

        $expired = $this->service->expireStaleAttempts();

        $this->assertSame(1, $expired);
        $this->assertSame('failed', $old->fresh()->status);
        $this->assertSame('pending', $recent->fresh()->status);
    }
}
