<?php

namespace Tests\Feature\Companies;

use App\Core\Tenancy\PermissionsTeam;
use App\Modules\Companies\Services\CompanyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyCreatedListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_company_through_the_service_seeds_default_roles(): void
    {
        $company = app(CompanyService::class)->create(['name' => 'Acme Property Group']);

        $roles = PermissionsTeam::runAs($company->id, fn () => Role::pluck('name')->all());

        $this->assertEqualsCanonicalizing(['Admin', 'Staff'], $roles);
    }

    public function test_the_admin_role_gets_every_permission(): void
    {
        $company = app(CompanyService::class)->create(['name' => 'Acme Property Group']);

        $admin = PermissionsTeam::runAs($company->id, fn () => Role::where('name', 'Admin')->firstOrFail());

        $this->assertSame(Permission::where('guard_name', 'web')->count(), $admin->permissions->count());
    }

    public function test_the_staff_role_only_gets_view_permissions(): void
    {
        $company = app(CompanyService::class)->create(['name' => 'Acme Property Group']);

        $staff = PermissionsTeam::runAs($company->id, fn () => Role::where('name', 'Staff')->firstOrFail());

        $this->assertTrue($staff->permissions->every(fn (Permission $permission) => str_contains($permission->name, '.view')));
        $this->assertGreaterThan(0, $staff->permissions->count());
    }

    public function test_each_company_gets_its_own_roles_scoped_by_team(): void
    {
        // Spatie's Role model doesn't scope plain queries by team (see
        // RoleService's docblock) - company_id is filtered explicitly
        // here, same as the app code has to.
        $service = app(CompanyService::class);
        $companyA = $service->create(['name' => 'Company A']);
        $companyB = $service->create(['name' => 'Company B']);

        $rolesA = Role::where('company_id', $companyA->id)->count();
        $rolesB = Role::where('company_id', $companyB->id)->count();

        $this->assertSame(2, $rolesA);
        $this->assertSame(2, $rolesB);

        // Confirm they're genuinely different rows, not shared/global ones.
        $idsA = Role::where('company_id', $companyA->id)->pluck('id')->all();
        $idsB = Role::where('company_id', $companyB->id)->pluck('id')->all();
        $this->assertEmpty(array_intersect($idsA, $idsB));
    }
}
