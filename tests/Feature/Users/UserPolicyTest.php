<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * hasPermissionTo() (and therefore these policies) reads whatever team
 * id is currently set on the PermissionRegistrar - normally set by
 * App\Core\Http\Middleware\IdentifyCompany during a real request. These
 * tests call policies directly (no HTTP round-trip), so every
 * assertion that should reflect "the actor is browsing within their
 * own company" is wrapped in PermissionsTeam::runAs($company->id, ...)
 * to reproduce that, exactly as CompanyWebTest/CompanyApiTest do via
 * actingAs() + real requests instead.
 */
class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_do_everything(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $target = User::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($owner->can('viewAny', User::class));
        $this->assertTrue($owner->can('view', $target));
        $this->assertTrue($owner->can('create', User::class));
        $this->assertTrue($owner->can('update', $target));
        $this->assertTrue($owner->can('delete', $target));
    }

    public function test_a_company_user_without_permissions_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $target = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($actor, $target): void {
            $this->assertFalse($actor->can('viewAny', User::class));
            $this->assertFalse($actor->can('view', $target));
            $this->assertFalse($actor->can('create', User::class));
            $this->assertFalse($actor->can('update', $target));
            $this->assertFalse($actor->can('delete', $target));
        });
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_users(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $target = User::factory()->create(['company_id' => $company->id]);

        $this->grantAllUserPermissions($actor, $company->id);

        PermissionsTeam::runAs($company->id, function () use ($actor, $target): void {
            $this->assertTrue($actor->can('viewAny', User::class));
            $this->assertTrue($actor->can('view', $target));
            $this->assertTrue($actor->can('create', User::class));
            $this->assertTrue($actor->can('update', $target));
            $this->assertTrue($actor->can('delete', $target));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $ownCompany->id]);
        $target = User::factory()->create(['company_id' => $otherCompany->id]);

        $this->grantAllUserPermissions($actor, $ownCompany->id);

        PermissionsTeam::runAs($ownCompany->id, function () use ($actor, $target): void {
            $this->assertFalse($actor->can('view', $target));
            $this->assertFalse($actor->can('update', $target));
            $this->assertFalse($actor->can('delete', $target));
        });
    }

    public function test_a_user_cannot_delete_themselves(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $this->grantAllUserPermissions($actor, $company->id);

        PermissionsTeam::runAs($company->id, function () use ($actor): void {
            $this->assertFalse($actor->can('delete', $actor));
        });

        $owner = User::factory()->create();
        $this->assertFalse($owner->can('delete', $owner));
    }

    protected function grantAllUserPermissions(User $user, int $companyId): void
    {
        PermissionsTeam::runAs($companyId, function () use ($user): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions([
                'users.viewAny', 'users.view', 'users.create', 'users.update', 'users.delete',
            ]);
            $user->assignRole($role);
        });
    }
}
