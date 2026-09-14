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
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentCollectionApiTest extends TestCase
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

    public function test_a_company_admin_can_charge_an_invoice_via_api(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/pay/cs_1'], 200)]);

        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $this->activateStripe($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id, 'total_amount' => 500]);

        $response = $this->postJson("/api/v1/invoices/{$invoice->uuid}/charge", ['amount' => 500]);

        $response->assertCreated()->assertJsonPath('data.status', 'pending');
    }

    public function test_verify_via_api_returns_the_updated_invoice(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'paid'], 200)]);

        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $this->activateStripe($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id, 'total_amount' => 500]);
        $attempt = InvoicePaymentAttempt::factory()->create([
            'company_id' => $company->id, 'invoice_id' => $invoice->id, 'provider' => 'stripe', 'reference' => 'cs_1', 'amount_minor' => 50000,
        ]);

        $response = $this->postJson("/api/v1/invoices/{$invoice->uuid}/payment-attempts/{$attempt->uuid}/verify");

        $response->assertOk()->assertJsonPath('data.status', 'paid');
    }

    public function test_webhook_verifies_a_matching_pending_attempt(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'paid'], 200)]);

        $company = Company::factory()->create();
        $this->activateStripe($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id, 'total_amount' => 500]);
        $attempt = InvoicePaymentAttempt::factory()->create([
            'company_id' => $company->id, 'invoice_id' => $invoice->id, 'provider' => 'stripe', 'reference' => 'cs_1', 'amount_minor' => 50000,
        ]);

        $response = $this->postJson('/api/v1/webhooks/payments/stripe', ['data' => ['object' => ['id' => 'cs_1']]]);

        $response->assertOk();
        $this->assertSame('successful', $attempt->fresh()->status);
    }

    public function test_webhook_with_an_unknown_reference_is_a_harmless_no_op(): void
    {
        $response = $this->postJson('/api/v1/webhooks/payments/stripe', ['data' => ['object' => ['id' => 'nonexistent']]]);

        $response->assertOk()->assertJsonPath('message', 'No matching pending attempt.');
    }

    public function test_webhook_without_a_reference_returns_a_422(): void
    {
        $this->postJson('/api/v1/webhooks/payments/stripe', [])->assertStatus(422);
    }
}
