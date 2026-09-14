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

class ExpenseWebTest extends TestCase
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

    public function test_saas_owner_cannot_access_expenses(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('expenses.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_record_an_unpaid_expense(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);

        $response = $this->actingAs($admin)->post(route('expenses.store'), [
            'account_id' => $account->id,
            'description' => 'Plumbing repair',
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', ['company_id' => $company->id, 'description' => 'Plumbing repair', 'payment_status' => 'unpaid']);
    }

    public function test_recording_a_paid_expense_without_a_payment_method_fails_validation(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);

        $response = $this->actingAs($admin)->post(route('expenses.store'), [
            'account_id' => $account->id,
            'description' => 'Plumbing repair',
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'paid',
        ]);

        $response->assertSessionHasErrors(['payment_method', 'paid_on']);
    }

    public function test_a_company_admin_cannot_view_another_companys_expense(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherAccount = Account::factory()->create(['company_id' => $otherCompany->id, 'type' => 'expense']);
        $otherExpense = Expense::factory()->create(['company_id' => $otherCompany->id, 'account_id' => $otherAccount->id]);

        $this->actingAs($admin)->get(route('expenses.show', $otherExpense))->assertForbidden();
    }

    public function test_marking_an_expense_as_paid(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $expense = Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id]);

        $response = $this->actingAs($admin)->post(route('expenses.mark-paid', $expense), [
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertSame('paid', $expense->fresh()->payment_status);
    }

    public function test_voiding_an_expense(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $expense = Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id]);

        $response = $this->actingAs($admin)->post(route('expenses.void', $expense), ['reason' => 'Duplicate']);

        $response->assertRedirect();
        $this->assertSame('voided', $expense->fresh()->status);
    }

    public function test_editing_only_updates_metadata_fields(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $expense = Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id, 'amount' => 150]);

        $response = $this->actingAs($admin)->put(route('expenses.update', $expense), [
            'description' => 'Updated description',
            'vendor_name' => 'New Vendor',
        ]);

        $response->assertRedirect();
        $expense->refresh();
        $this->assertSame('Updated description', $expense->description);
        $this->assertSame(150.0, (float) $expense->amount);
    }
}
