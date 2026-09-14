<?php

namespace Tests\Feature\Accounting;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountApiTest extends TestCase
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

    public function test_a_company_admin_can_list_their_own_accounts_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        Account::factory()->count(2)->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/accounts')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_company_admin_can_create_an_account_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/accounts', ['code' => '6000', 'name' => 'Advertising Expense', 'type' => 'expense']);

        $response->assertCreated()->assertJsonPath('data.code', '6000');
    }

    public function test_a_company_admin_cannot_view_another_companys_account_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherAccount = Account::factory()->create(['company_id' => $otherCompany->id]);

        $this->getJson("/api/v1/accounts/{$otherAccount->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_account_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $account = Account::factory()->create(['company_id' => $company->id]);

        $this->deleteJson("/api/v1/accounts/{$account->uuid}")->assertNoContent();

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }
}
