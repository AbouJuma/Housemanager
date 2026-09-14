<?php

namespace Tests\Feature\Tenants;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Tenant Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_tenants(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($owner->can('viewAny', Tenant::class));
        $this->assertFalse($owner->can('view', $tenant));
        $this->assertFalse($owner->can('create', Tenant::class));
    }

    public function test_a_company_user_without_permissions_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($user->can('viewAny', Tenant::class));
        $this->assertFalse($user->can('view', $tenant));
        $this->assertFalse($user->can('update', $tenant));
        $this->assertFalse($user->can('delete', $tenant));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_tenants(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['tenants.viewAny', 'tenants.view', 'tenants.create', 'tenants.update', 'tenants.delete']);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $tenant): void {
            $this->assertTrue($user->can('viewAny', Tenant::class));
            $this->assertTrue($user->can('view', $tenant));
            $this->assertTrue($user->can('create', Tenant::class));
            $this->assertTrue($user->can('update', $tenant));
            $this->assertTrue($user->can('delete', $tenant));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['tenants.view', 'tenants.update', 'tenants.delete']);
        $otherTenant = Tenant::factory()->create(['company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherTenant): void {
            $this->assertFalse($user->can('view', $otherTenant));
            $this->assertFalse($user->can('update', $otherTenant));
            $this->assertFalse($user->can('delete', $otherTenant));
        });
    }
}
