<?php

namespace Tests\Feature\Expenses;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpensePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeExpense(Company $company): Expense
    {
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);

        return Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id]);
    }

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Bookkeeper', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_expenses(): void
    {
        $owner = User::factory()->create();
        $expense = $this->makeExpense(Company::factory()->create());

        $this->assertFalse($owner->can('viewAny', Expense::class));
        $this->assertFalse($owner->can('view', $expense));
        $this->assertFalse($owner->can('create', Expense::class));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_expenses(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['expenses.viewAny', 'expenses.view', 'expenses.create', 'expenses.update']);
        $expense = $this->makeExpense($company);

        PermissionsTeam::runAs($company->id, function () use ($user, $expense): void {
            $this->assertTrue($user->can('viewAny', Expense::class));
            $this->assertTrue($user->can('view', $expense));
            $this->assertTrue($user->can('create', Expense::class));
            $this->assertTrue($user->can('update', $expense));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['expenses.view', 'expenses.update']);
        $otherExpense = $this->makeExpense($otherCompany);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherExpense): void {
            $this->assertFalse($user->can('view', $otherExpense));
            $this->assertFalse($user->can('update', $otherExpense));
        });
    }
}
