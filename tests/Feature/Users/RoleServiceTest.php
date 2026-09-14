<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Modules\Companies\Models\Company;
use App\Modules\Users\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RoleService::class);
    }

    public function test_create_scopes_the_role_to_the_given_company(): void
    {
        $company = Company::factory()->create();

        $role = $this->service->create($company->id, [
            'name' => 'Property Manager',
            'permissions' => ['users.viewAny'],
        ]);

        $this->assertSame($company->id, $role->company_id);
        $this->assertTrue($role->permissions->pluck('name')->contains('users.viewAny'));
    }

    public function test_two_companies_can_have_a_role_with_the_same_name(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $roleA = $this->service->create($companyA->id, ['name' => 'Manager', 'permissions' => []]);
        $roleB = $this->service->create($companyB->id, ['name' => 'Manager', 'permissions' => []]);

        $this->assertNotSame($roleA->id, $roleB->id);
        $this->assertSame($companyA->id, $roleA->company_id);
        $this->assertSame($companyB->id, $roleB->company_id);
    }

    public function test_update_renames_and_resyncs_permissions(): void
    {
        $company = Company::factory()->create();
        $role = $this->service->create($company->id, ['name' => 'Manager', 'permissions' => ['users.viewAny']]);

        $updated = $this->service->update($role, [
            'name' => 'Senior Manager',
            'permissions' => ['users.viewAny', 'users.update'],
        ]);

        $this->assertSame('Senior Manager', $updated->name);
        $this->assertEqualsCanonicalizing(
            ['users.viewAny', 'users.update'],
            $updated->permissions->pluck('name')->all()
        );
    }

    public function test_delete_removes_the_role(): void
    {
        $company = Company::factory()->create();
        $role = $this->service->create($company->id, ['name' => 'Temp', 'permissions' => []]);

        $this->assertTrue($this->service->delete($role));

        $exists = PermissionsTeam::runAs($company->id, fn () => \Spatie\Permission\Models\Role::find($role->id));
        $this->assertNull($exists);
    }
}
