<?php

namespace Tests\Feature\Vendors;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company, array $extraPermissions = []): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin, $extraPermissions): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions([
                'vendors.viewAny', 'vendors.view', 'vendors.create', 'vendors.update', 'vendors.delete',
                ...$extraPermissions,
            ]);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_saas_owner_cannot_access_vendors(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('vendors.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_list_their_own_vendors(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Vendor::factory()->count(2)->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->get(route('vendors.index'));

        $response->assertOk();
        $response->assertViewHas('vendors', fn ($vendors) => $vendors->total() === 2);
    }

    public function test_a_company_admin_can_create_a_vendor_with_bank_details(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('vendors.store'), [
            'name' => 'Ace Plumbing',
            'type' => 'company',
            'category' => 'plumbing',
            'bank_name' => 'CRDB Bank',
            'bank_account_number' => '1234567890',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vendors', ['name' => 'Ace Plumbing', 'company_id' => $company->id]);
        $vendor = Vendor::where('name', 'Ace Plumbing')->first();
        $this->assertSame('1234567890', $vendor->bankDetail('account_number'));
    }

    public function test_a_company_admin_cannot_view_another_companys_vendor(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherVendor = Vendor::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('vendors.show', $otherVendor))->assertForbidden();
    }

    public function test_deleting_a_vendor_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->delete(route('vendors.destroy', $vendor));

        $response->assertRedirect(route('vendors.index'));
        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    public function test_recording_an_expense_against_a_formal_vendor(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company, ['expenses.viewAny', 'expenses.view', 'expenses.create', 'expenses.update']);
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);

        $response = $this->actingAs($admin)->post(route('expenses.store'), [
            'account_id' => $account->id,
            'vendor_id' => $vendor->id,
            'description' => 'Plumbing repair',
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $response->assertRedirect();
        $expense = Expense::where('description', 'Plumbing repair')->first();
        $this->assertSame($vendor->id, $expense->vendor_id);
        $this->assertSame($vendor->name, $expense->vendorDisplayName());
    }

    public function test_an_expense_cannot_link_to_another_companys_vendor(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company, ['expenses.viewAny', 'expenses.view', 'expenses.create', 'expenses.update']);
        $otherVendor = Vendor::factory()->create(['company_id' => $otherCompany->id]);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);

        $response = $this->actingAs($admin)->post(route('expenses.store'), [
            'account_id' => $account->id,
            'vendor_id' => $otherVendor->id,
            'description' => 'Plumbing repair',
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $response->assertSessionHasErrors('vendor_id');
    }
}
