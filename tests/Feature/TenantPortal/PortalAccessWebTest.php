<?php

namespace Tests\Feature\TenantPortal;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalAccessWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['tenants.viewAny', 'tenants.view', 'tenants.create', 'tenants.update', 'tenants.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_a_company_admin_can_enable_portal_access_and_sees_the_password_once(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => 'tenant@example.test']);

        $response = $this->actingAs($admin)->post(route('tenants.portal-access.enable', $tenant));

        $response->assertRedirect(route('tenants.show', $tenant));
        $response->assertSessionHas('portal_password');
        $this->assertTrue($tenant->fresh()->hasPortalAccess());
    }

    public function test_a_company_admin_can_disable_portal_access(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => 'tenant@example.test']);
        $this->actingAs($admin)->post(route('tenants.portal-access.enable', $tenant));

        $response = $this->actingAs($admin)->post(route('tenants.portal-access.disable', $tenant));

        $response->assertRedirect(route('tenants.show', $tenant));
        $this->assertFalse($tenant->fresh()->hasPortalAccess());
    }

    public function test_a_company_admin_cannot_manage_another_companys_tenant_portal_access(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherTenant = Tenant::factory()->create(['company_id' => $otherCompany->id, 'email' => 'other@example.test']);

        $this->actingAs($admin)->post(route('tenants.portal-access.enable', $otherTenant))->assertForbidden();
    }
}
