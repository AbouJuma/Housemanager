<?php

namespace Tests\Feature\Expenses;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['expenses.viewAny', 'expenses.view', 'expenses.create', 'expenses.update']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_a_company_admin_can_create_an_expense_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);

        $response = $this->postJson('/api/v1/expenses', [
            'account_id' => $account->id,
            'description' => 'Plumbing repair',
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $response->assertCreated()->assertJsonPath('data.payment_status', 'unpaid');
    }

    public function test_a_company_admin_cannot_view_another_companys_expense_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherAccount = Account::factory()->create(['company_id' => $otherCompany->id, 'type' => 'expense']);
        $otherExpense = Expense::factory()->create(['company_id' => $otherCompany->id, 'account_id' => $otherAccount->id]);

        $this->getJson("/api/v1/expenses/{$otherExpense->uuid}")->assertForbidden();
    }

    public function test_mark_paid_and_void_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $expense = Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id]);

        $this->postJson("/api/v1/expenses/{$expense->uuid}/mark-paid", [
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ])->assertOk()->assertJsonPath('data.payment_status', 'paid');

        $this->postJson("/api/v1/expenses/{$expense->uuid}/void")
            ->assertOk()->assertJsonPath('data.status', 'voided');
    }
}
