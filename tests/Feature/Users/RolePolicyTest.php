<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_cannot_manage_roles(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $role = PermissionsTeam::runAs($company->id, fn () => Role::findOrCreate('Custom', 'web'));

        $this->assertFalse($owner->can('viewAny', Role::class));
        $this->assertFalse($owner->can('view', $role));
        $this->assertFalse($owner->can('create', Role::class));
        $this->assertFalse($owner->can('update', $role));
        $this->assertFalse($owner->can('delete', $role));
    }

    public function test_a_company_user_without_permissions_cannot_manage_roles(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $role = PermissionsTeam::runAs($company->id, fn () => Role::findOrCreate('Custom', 'web'));

        PermissionsTeam::runAs($company->id, function () use ($actor, $role): void {
            $this->assertFalse($actor->can('viewAny', Role::class));
            $this->assertFalse($actor->can('view', $role));
            $this->assertFalse($actor->can('create', Role::class));
            $this->assertFalse($actor->can('update', $role));
            $this->assertFalse($actor->can('delete', $role));
        });
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_roles(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $role = PermissionsTeam::runAs($company->id, fn () => Role::findOrCreate('Custom', 'web'));

        $this->grantAllRolePermissions($actor, $company->id);

        PermissionsTeam::runAs($company->id, function () use ($actor, $role): void {
            $this->assertTrue($actor->can('viewAny', Role::class));
            $this->assertTrue($actor->can('view', $role));
            $this->assertTrue($actor->can('create', Role::class));
            $this->assertTrue($actor->can('update', $role));
            $this->assertTrue($actor->can('delete', $role));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $ownCompany->id]);
        $otherRole = PermissionsTeam::runAs($otherCompany->id, fn () => Role::findOrCreate('Custom', 'web'));

        $this->grantAllRolePermissions($actor, $ownCompany->id);

        PermissionsTeam::runAs($ownCompany->id, function () use ($actor, $otherRole): void {
            $this->assertFalse($actor->can('view', $otherRole));
            $this->assertFalse($actor->can('update', $otherRole));
            $this->assertFalse($actor->can('delete', $otherRole));
        });
    }

    protected function grantAllRolePermissions(User $user, int $companyId): void
    {
        PermissionsTeam::runAs($companyId, function () use ($user): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions([
                'roles.viewAny', 'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            ]);
            $user->assignRole($role);
        });
    }
}
