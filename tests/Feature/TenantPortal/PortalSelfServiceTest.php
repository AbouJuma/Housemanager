<?php

namespace Tests\Feature\TenantPortal;

use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortalSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeLease(Company $company, Tenant $tenant, array $overrides = []): Lease
    {
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id]);

        return Lease::factory()->create(array_merge([
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
        ], $overrides));
    }

    protected function makePortalTenant(Company $company): Tenant
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => "tenant-{$company->id}@example.test"]);
        $tenant->forceFill(['password' => 'correct-password'])->save();

        return $tenant;
    }

    public function test_dashboard_shows_only_the_authenticated_tenants_own_data(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);
        $this->makeLease($company, $tenant);

        $response = $this->actingAs($tenant, 'tenant')->get(route('portal.dashboard'));

        $response->assertOk();
        $response->assertViewHas('activeLeases', fn ($leases) => $leases->count() === 1);
    }

    public function test_a_tenant_can_view_their_own_lease_but_not_anothers(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);
        $ownLease = $this->makeLease($company, $tenant);

        $otherTenant = Tenant::factory()->create(['company_id' => $company->id]);
        $otherLease = $this->makeLease($company, $otherTenant);

        $this->actingAs($tenant, 'tenant')->get(route('portal.leases.show', $ownLease))->assertOk();
        $this->actingAs($tenant, 'tenant')->get(route('portal.leases.show', $otherLease))->assertForbidden();
    }

    public function test_a_tenant_can_view_their_own_invoice_but_not_anothers(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);
        $ownLease = $this->makeLease($company, $tenant);
        $ownInvoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $ownLease->id]);

        $otherTenant = Tenant::factory()->create(['company_id' => $company->id]);
        $otherLease = $this->makeLease($company, $otherTenant);
        $otherInvoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $otherLease->id]);

        $this->actingAs($tenant, 'tenant')->get(route('portal.invoices.show', $ownInvoice))->assertOk();
        $this->actingAs($tenant, 'tenant')->get(route('portal.invoices.show', $otherInvoice))->assertForbidden();
    }

    public function test_a_tenant_can_pay_their_own_invoice(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/pay/cs_1'], 200)]);
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'is_active' => true,
            'credentials' => ['api_secret' => 'sk_test_xxx'],
        ]);
        $tenant = $this->makePortalTenant($company);
        $lease = $this->makeLease($company, $tenant);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'lease_id' => $lease->id,
            'status' => 'sent',
            'total_amount' => 500,
            'amount_paid' => 0,
        ]);

        $response = $this->actingAs($tenant, 'tenant')->post(route('portal.invoices.pay', $invoice), ['amount' => 100]);

        $response->assertRedirect(route('portal.invoices.show', $invoice));
        $this->assertDatabaseHas('invoice_payment_attempts', ['invoice_id' => $invoice->id, 'initiated_by_id' => null]);
    }

    public function test_a_tenant_cannot_pay_anothers_invoice(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);
        $this->makeLease($company, $tenant);

        $otherTenant = Tenant::factory()->create(['company_id' => $company->id]);
        $otherLease = $this->makeLease($company, $otherTenant);
        $otherInvoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'lease_id' => $otherLease->id,
            'status' => 'sent',
            'total_amount' => 500,
            'amount_paid' => 0,
        ]);

        $this->actingAs($tenant, 'tenant')
            ->post(route('portal.invoices.pay', $otherInvoice), ['amount' => 100])
            ->assertForbidden();

        $this->assertDatabaseMissing('invoice_payment_attempts', ['invoice_id' => $otherInvoice->id]);
    }

    public function test_a_tenant_can_submit_a_maintenance_request_against_their_own_lease(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);
        $lease = $this->makeLease($company, $tenant);

        $response = $this->actingAs($tenant, 'tenant')->post(route('portal.maintenance-requests.store'), [
            'lease_id' => $lease->id,
            'title' => 'Leaking faucet',
            'category' => 'plumbing',
            'priority' => 'high',
        ]);

        $response->assertRedirect(route('portal.maintenance-requests.index'));
        $this->assertDatabaseHas('maintenance_requests', [
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'property_id' => $lease->unit->building->property_id,
            'unit_id' => $lease->unit_id,
            'title' => 'Leaking faucet',
        ]);
    }

    public function test_a_tenant_cannot_submit_a_maintenance_request_against_someone_elses_lease(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);
        $otherTenant = Tenant::factory()->create(['company_id' => $company->id]);
        $otherLease = $this->makeLease($company, $otherTenant);

        $response = $this->actingAs($tenant, 'tenant')->post(route('portal.maintenance-requests.store'), [
            'lease_id' => $otherLease->id,
            'title' => 'Leaking faucet',
            'category' => 'plumbing',
            'priority' => 'high',
        ]);

        $response->assertSessionHasErrors('lease_id');
    }

    public function test_documents_index_shows_only_documents_attached_to_the_tenant_or_their_own_lease(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);
        $lease = $this->makeLease($company, $tenant);

        Document::factory()->create(['company_id' => $company->id, 'documentable_type' => Tenant::class, 'documentable_id' => $tenant->id, 'title' => 'My ID Copy']);
        Document::factory()->create(['company_id' => $company->id, 'documentable_type' => Lease::class, 'documentable_id' => $lease->id, 'title' => 'My Lease Agreement']);

        $otherTenant = Tenant::factory()->create(['company_id' => $company->id]);
        Document::factory()->create(['company_id' => $company->id, 'documentable_type' => Tenant::class, 'documentable_id' => $otherTenant->id, 'title' => 'Someone Elses Document']);

        $response = $this->actingAs($tenant, 'tenant')->get(route('portal.documents.index'));

        $response->assertOk();
        $response->assertSee('My ID Copy');
        $response->assertSee('My Lease Agreement');
        $response->assertDontSee('Someone Elses Document');
    }
}
