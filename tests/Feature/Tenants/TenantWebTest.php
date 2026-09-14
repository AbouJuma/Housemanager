<?php

namespace Tests\Feature\Tenants;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantWebTest extends TestCase
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

    public function test_saas_owner_cannot_access_tenants(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('tenants.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_list_their_own_tenants(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Tenant::factory()->count(2)->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->get(route('tenants.index'));

        $response->assertOk();
        $response->assertViewHas('tenants', fn ($tenants) => $tenants->total() === 2);
    }

    public function test_a_company_admin_can_create_a_tenant_with_identification(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('tenants.store'), [
            'name' => 'Jane Doe',
            'type' => 'individual',
            'identification_type' => 'national_id',
            'identification_number' => 'ID-12345678',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', ['name' => 'Jane Doe', 'company_id' => $company->id]);
        $tenant = Tenant::where('name', 'Jane Doe')->first();
        $this->assertSame('ID-12345678', $tenant->identification_number);
    }

    public function test_a_company_admin_cannot_view_another_companys_tenant(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherTenant = Tenant::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('tenants.show', $otherTenant))->assertForbidden();
    }

    public function test_deleting_a_tenant_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->delete(route('tenants.destroy', $tenant));

        $response->assertRedirect(route('tenants.index'));
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }
}
