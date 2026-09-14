<?php

namespace Tests\Feature\Invoices;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceWebTest extends TestCase
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

    public function test_saas_owner_cannot_access_invoices(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('invoices.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_list_their_own_invoices(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Invoice::factory()->count(2)->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $response = $this->actingAs($admin)->get(route('invoices.index'));

        $response->assertOk();
        $response->assertViewHas('invoices', fn ($invoices) => $invoices->total() === 2);
    }

    public function test_a_company_admin_can_create_an_invoice_with_line_items(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $lease = $this->makeLease($company);

        $response = $this->actingAs($admin)->post(route('invoices.store'), [
            'lease_id' => $lease->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [
                ['description' => 'Rent', 'quantity' => 1, 'unit_price' => 500],
                ['description' => 'Water', 'quantity' => 2, 'unit_price' => 25],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['company_id' => $company->id, 'lease_id' => $lease->id, 'total_amount' => 550]);
    }

    public function test_creating_an_invoice_for_another_companys_lease_fails_validation(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherLease = $this->makeLease($otherCompany);

        $response = $this->actingAs($admin)->post(route('invoices.store'), [
            'lease_id' => $otherLease->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [['description' => 'Rent', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        $response->assertSessionHasErrors('lease_id');
    }

    public function test_a_company_admin_cannot_view_another_companys_invoice(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherInvoice = Invoice::factory()->create(['company_id' => $otherCompany->id, 'lease_id' => $this->makeLease($otherCompany)->id]);

        $this->actingAs($admin)->get(route('invoices.show', $otherInvoice))->assertForbidden();
    }

    public function test_sending_an_invoice(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $invoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $response = $this->actingAs($admin)->post(route('invoices.send', $invoice));

        $response->assertRedirect();
        $this->assertSame('sent', $invoice->fresh()->status);
    }

    public function test_editing_a_sent_invoice_is_rejected(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $response = $this->actingAs($admin)->put(route('invoices.update', $invoice), [
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'items' => [['description' => 'Rent', 'quantity' => 1, 'unit_price' => 999]],
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_recording_a_payment_updates_the_balance(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id, 'total_amount' => 500]);

        $response = $this->actingAs($admin)->post(route('invoices.payments.store', $invoice), [
            'amount' => 500,
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $response->assertRedirect();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertDatabaseHas('invoice_payments', ['invoice_id' => $invoice->id, 'amount' => 500, 'recorded_by_id' => $admin->id]);
    }

    public function test_cancelling_an_invoice(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $response = $this->actingAs($admin)->post(route('invoices.cancel', $invoice));

        $response->assertRedirect();
        $this->assertSame('cancelled', $invoice->fresh()->status);
    }

    public function test_deleting_a_draft_invoice_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $invoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $response = $this->actingAs($admin)->delete(route('invoices.destroy', $invoice));

        $response->assertRedirect(route('invoices.index'));
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_deleting_a_sent_invoice_is_rejected(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $invoice = Invoice::factory()->sent()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $response = $this->actingAs($admin)->delete(route('invoices.destroy', $invoice));

        $response->assertSessionHasErrors('status');
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice->id]);
    }
}
