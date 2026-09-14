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
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerifyPendingRentPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSentInvoice(Company $company, float $total = 500): Invoice
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        return Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $lease->id, 'total_amount' => $total]);
    }

    public function test_it_verifies_every_pending_attempt_and_expires_stale_ones(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'paid'], 200)]);

        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe', 'is_active' => true,
            'credentials' => ['api_secret' => 'sk_test_xxx'],
        ]);
        $invoice = $this->makeSentInvoice($company, 500);
        $fresh = InvoicePaymentAttempt::factory()->create([
            'company_id' => $company->id, 'invoice_id' => $invoice->id, 'provider' => 'stripe', 'reference' => 'cs_1', 'amount_minor' => 50000,
        ]);
        // A provider with no configured Integration row for this
        // company - PaymentManager::verify() short-circuits to
        // success:false without any HTTP call (same as
        // PaymentManagerTest's "never configured" case), so this stays
        // 'pending' through the verify pass and is left for
        // expireStaleAttempts() to resolve based on its age, not a
        // gateway response - the whole point of this test.
        $stale = InvoicePaymentAttempt::factory()->create([
            'company_id' => $company->id, 'invoice_id' => $invoice->id, 'provider' => 'mpesa', 'reference' => 'unrelated',
        ]);
        $stale->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->artisan('rent:verify-pending-payments')->assertSuccessful();

        $this->assertSame('successful', $fresh->fresh()->status);
        $this->assertSame('failed', $stale->fresh()->status);
    }
}
