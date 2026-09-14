<?php

namespace Tests\Feature\Maintenance;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceRequestWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['maintenance.viewAny', 'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_saas_owner_cannot_access_maintenance_requests(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('maintenance-requests.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_log_a_maintenance_request(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->post(route('maintenance-requests.store'), [
            'property_id' => $property->id,
            'title' => 'Leaking faucet',
            'category' => 'plumbing',
            'priority' => 'high',
            'reported_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('maintenance_requests', ['company_id' => $company->id, 'title' => 'Leaking faucet', 'reported_by_id' => $admin->id]);
    }

    public function test_a_company_admin_cannot_view_another_companys_request(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherRequest = MaintenanceRequest::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);

        $this->actingAs($admin)->get(route('maintenance-requests.show', $otherRequest))->assertForbidden();
    }

    public function test_updating_status_to_completed_sets_the_completed_date(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $request = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $response = $this->actingAs($admin)->put(route('maintenance-requests.update', $request), [
            'property_id' => $property->id,
            'title' => $request->title,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'completed',
            'reported_date' => $request->reported_date->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertNotNull($request->fresh()->completed_date);
    }

    public function test_recording_an_expense_against_a_maintenance_request(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $request = MaintenanceRequest::factory()->completed()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->actingAs($admin)->post(route('maintenance-requests.record-expense', $request), [
            'account_id' => $account->id,
            'amount' => 200,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertNotNull($request->fresh()->expense_id);
        $this->assertDatabaseHas('expenses', ['vendor_id' => $vendor->id, 'amount' => 200]);
    }

    public function test_deleting_a_maintenance_request_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $request = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $response = $this->actingAs($admin)->delete(route('maintenance-requests.destroy', $request));

        $response->assertRedirect(route('maintenance-requests.index'));
        $this->assertSoftDeleted('maintenance_requests', ['id' => $request->id]);
    }
}
