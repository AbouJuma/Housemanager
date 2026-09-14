<?php

namespace Tests\Feature\Maintenance;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceRequestApiTest extends TestCase
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

    public function test_a_company_admin_can_list_their_own_requests_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);
        MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->getJson('/api/v1/maintenance-requests')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_company_admin_can_create_a_request_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $response = $this->postJson('/api/v1/maintenance-requests', [
            'property_id' => $property->id,
            'title' => 'Leaking faucet',
            'category' => 'plumbing',
            'priority' => 'high',
            'reported_date' => now()->toDateString(),
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Leaking faucet');
    }

    public function test_a_company_admin_cannot_view_another_companys_request_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherRequest = MaintenanceRequest::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);

        $this->getJson("/api/v1/maintenance-requests/{$otherRequest->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_request_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $request = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->deleteJson("/api/v1/maintenance-requests/{$request->uuid}")->assertNoContent();

        $this->assertSoftDeleted('maintenance_requests', ['id' => $request->id]);
    }
}
