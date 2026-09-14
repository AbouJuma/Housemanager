<?php

namespace Tests\Feature\Tenants;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantApiTest extends TestCase
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

    public function test_a_company_admin_can_list_their_own_tenants_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        Tenant::factory()->count(2)->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/tenants')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_company_admin_can_create_a_tenant_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'Jane Doe',
            'type' => 'individual',
            'identification_number' => 'ID-12345678',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.identification_number', 'ID-12345678');
    }

    public function test_a_company_admin_cannot_view_another_companys_tenant_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherTenant = Tenant::factory()->create(['company_id' => $otherCompany->id]);

        $this->getJson("/api/v1/tenants/{$otherTenant->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_tenant_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        $this->deleteJson("/api/v1/tenants/{$tenant->uuid}")->assertNoContent();

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }
}
