<?php

namespace Tests\Feature\Accounting;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['accounts.viewAny', 'accounts.view', 'accounts.create', 'accounts.update', 'accounts.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_saas_owner_cannot_access_accounts(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('accounts.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_list_their_own_accounts(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Account::factory()->count(2)->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->get(route('accounts.index'));

        $response->assertOk();
        $response->assertViewHas('accounts', fn ($accounts) => $accounts->total() === 2);
    }

    public function test_a_company_admin_can_create_an_account(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('accounts.store'), [
            'code' => '6000',
            'name' => 'Advertising Expense',
            'type' => 'expense',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', ['company_id' => $company->id, 'code' => '6000', 'name' => 'Advertising Expense']);
    }

    public function test_a_duplicate_code_within_the_same_company_fails_validation(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Account::factory()->create(['company_id' => $company->id, 'code' => '6000']);

        $response = $this->actingAs($admin)->post(route('accounts.store'), [
            'code' => '6000',
            'name' => 'Another Account',
            'type' => 'expense',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_the_same_code_is_allowed_across_different_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Account::factory()->create(['company_id' => $companyA->id, 'code' => '6000']);
        $adminB = $this->makeCompanyAdmin($companyB);

        $response = $this->actingAs($adminB)->post(route('accounts.store'), [
            'code' => '6000',
            'name' => 'Advertising Expense',
            'type' => 'expense',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', ['company_id' => $companyB->id, 'code' => '6000']);
    }

    public function test_a_company_admin_cannot_view_another_companys_account(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherAccount = Account::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('accounts.show', $otherAccount))->assertForbidden();
    }

    public function test_deleting_an_account_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $account = Account::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->delete(route('accounts.destroy', $account));

        $response->assertRedirect(route('accounts.index'));
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }
}
