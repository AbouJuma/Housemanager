<?php

namespace Tests\Feature\RentCollection;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentCollectionWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['invoices.viewAny', 'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    protected function makeLease(Company $company): Lease
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);

        return Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
    }

    protected function activateStripe(Company $company): void
    {
        Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'is_active' => true,
            'credentials' => ['api_secret' => 'sk_test_xxx'],
        ]);
    }

    public function test_charging_an_invoice_creates_a_pending_attempt(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/pay/cs_1'], 200)]);

        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $this->activateStripe($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id, 'total_amount' => 500]);

        $response = $this->actingAs($admin)->post(route('invoices.charge', $invoice), ['amount' => 500]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseHas('invoice_payment_attempts', ['invoice_id' => $invoice->id, 'status' => 'pending', 'initiated_by_id' => $admin->id]);
    }

    public function test_charging_an_invoice_without_an_active_provider_fails_validation(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id, 'total_amount' => 500]);

        $response = $this->actingAs($admin)->post(route('invoices.charge', $invoice), ['amount' => 500]);

        $response->assertSessionHasErrors('provider');
    }

    public function test_a_company_admin_cannot_charge_another_companys_invoice(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherInvoice = Invoice::factory()->sent()->create(['company_id' => $otherCompany->id, 'lease_id' => $this->makeLease($otherCompany)->id]);

        $this->actingAs($admin)->post(route('invoices.charge', $otherInvoice), ['amount' => 100])->assertForbidden();
    }

    public function test_checking_status_verifies_and_redirects(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'paid'], 200)]);

        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $this->activateStripe($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id, 'total_amount' => 500]);
        $attempt = InvoicePaymentAttempt::factory()->create([
            'company_id' => $company->id, 'invoice_id' => $invoice->id, 'provider' => 'stripe', 'reference' => 'cs_1', 'amount_minor' => 50000,
        ]);

        $response = $this->actingAs($admin)->post(route('invoices.payment-attempts.verify', [$invoice, $attempt]));

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame('successful', $attempt->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_checking_status_for_an_attempt_belonging_to_a_different_invoice_404s(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $invoiceA = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);
        $invoiceB = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);
        $attempt = InvoicePaymentAttempt::factory()->create(['company_id' => $company->id, 'invoice_id' => $invoiceB->id]);

        $this->actingAs($admin)->post(route('invoices.payment-attempts.verify', [$invoiceA, $attempt]))->assertNotFound();
    }
}
