<?php

namespace Tests\Feature\Maintenance;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeRequest(Company $company): MaintenanceRequest
    {
        $property = Property::factory()->create(['company_id' => $company->id]);

        return MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
    }

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Maintenance Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_maintenance_requests(): void
    {
        $owner = User::factory()->create();
        $request = $this->makeRequest(Company::factory()->create());

        $this->assertFalse($owner->can('viewAny', MaintenanceRequest::class));
        $this->assertFalse($owner->can('view', $request));
        $this->assertFalse($owner->can('create', MaintenanceRequest::class));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_requests(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['maintenance.viewAny', 'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.delete']);
        $request = $this->makeRequest($company);

        PermissionsTeam::runAs($company->id, function () use ($user, $request): void {
            $this->assertTrue($user->can('viewAny', MaintenanceRequest::class));
            $this->assertTrue($user->can('view', $request));
            $this->assertTrue($user->can('create', MaintenanceRequest::class));
            $this->assertTrue($user->can('update', $request));
            $this->assertTrue($user->can('delete', $request));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['maintenance.view', 'maintenance.update', 'maintenance.delete']);
        $otherRequest = $this->makeRequest($otherCompany);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherRequest): void {
            $this->assertFalse($user->can('view', $otherRequest));
            $this->assertFalse($user->can('update', $otherRequest));
            $this->assertFalse($user->can('delete', $otherRequest));
        });
    }
}
