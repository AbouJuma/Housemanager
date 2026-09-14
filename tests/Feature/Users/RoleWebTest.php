<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_cannot_access_role_management(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('roles.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_list_their_roles(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('roles.index'))->assertOk();
    }

    public function test_a_company_admin_can_create_a_role_with_permissions(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Property Manager',
            'permissions' => ['users.viewAny', 'users.view'],
        ]);

        $role = PermissionsTeam::runAs($company->id, fn () => Role::where('name', 'Property Manager')->first());
        $this->assertNotNull($role);
        $response->assertRedirect(route('roles.edit', $role));
        $this->assertEqualsCanonicalizing(
            ['users.viewAny', 'users.view'],
            $role->permissions->pluck('name')->all()
        );
    }

    public function test_a_company_admin_cannot_edit_another_companys_role(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($ownCompany);
        $otherRole = PermissionsTeam::runAs($otherCompany->id, fn () => Role::findOrCreate('Custom', 'web'));

        $this->actingAs($admin)->get(route('roles.edit', $otherRole))->assertForbidden();
    }

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions([
                'roles.viewAny', 'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            ]);
            $admin->assignRole($role);
        });

        return $admin;
    }
}
