<?php

namespace Tests\Feature\Accounting;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Accountant', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_accounts(): void
    {
        $owner = User::factory()->create();
        $account = Account::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($owner->can('viewAny', Account::class));
        $this->assertFalse($owner->can('view', $account));
        $this->assertFalse($owner->can('create', Account::class));
    }

    public function test_a_company_user_without_permissions_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $account = Account::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($user->can('viewAny', Account::class));
        $this->assertFalse($user->can('view', $account));
        $this->assertFalse($user->can('update', $account));
        $this->assertFalse($user->can('delete', $account));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_accounts(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['accounts.viewAny', 'accounts.view', 'accounts.create', 'accounts.update', 'accounts.delete']);
        $account = Account::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $account): void {
            $this->assertTrue($user->can('viewAny', Account::class));
            $this->assertTrue($user->can('view', $account));
            $this->assertTrue($user->can('create', Account::class));
            $this->assertTrue($user->can('update', $account));
            $this->assertTrue($user->can('delete', $account));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['accounts.view', 'accounts.update', 'accounts.delete']);
        $otherAccount = Account::factory()->create(['company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherAccount): void {
            $this->assertFalse($user->can('view', $otherAccount));
            $this->assertFalse($user->can('update', $otherAccount));
            $this->assertFalse($user->can('delete', $otherAccount));
        });
    }
}
