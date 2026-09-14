<?php

namespace Tests\Feature\Invoices;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
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

    public function test_a_company_admin_can_list_their_own_invoices_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $this->getJson('/api/v1/invoices')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_company_admin_can_create_an_invoice_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $lease = $this->makeLease($company);

        $response = $this->postJson('/api/v1/invoices', [
            'lease_id' => $lease->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [['description' => 'Rent', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total_amount', 500);
    }

    public function test_a_company_admin_cannot_view_another_companys_invoice_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherInvoice = Invoice::factory()->create(['company_id' => $otherCompany->id, 'lease_id' => $this->makeLease($otherCompany)->id]);

        $this->getJson("/api/v1/invoices/{$otherInvoice->uuid}")->assertForbidden();
    }

    public function test_send_and_record_payment_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $invoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id, 'total_amount' => 500]);

        $this->postJson("/api/v1/invoices/{$invoice->uuid}/send")->assertOk()->assertJsonPath('data.status', 'sent');

        $this->postJson("/api/v1/invoices/{$invoice->uuid}/payments", [
            'amount' => 500,
            'paid_on' => now()->toDateString(),
            'method' => 'mobile_money',
        ])->assertOk()->assertJsonPath('data.status', 'paid');
    }

    public function test_a_company_admin_can_delete_their_own_draft_invoice_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $invoice = Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);

        $this->deleteJson("/api/v1/invoices/{$invoice->uuid}")->assertNoContent();

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }
}
