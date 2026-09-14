<?php

namespace Tests\Feature\Accounting;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['journal-entries.viewAny', 'journal-entries.view', 'journal-entries.create', 'journal-entries.void']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    protected function makeAccounts(Company $company): array
    {
        return [
            Account::factory()->create(['company_id' => $company->id, 'code' => '1000', 'type' => 'asset']),
            Account::factory()->create(['company_id' => $company->id, 'code' => '3000', 'type' => 'equity']),
        ];
    }

    public function test_saas_owner_cannot_access_journal_entries(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('journal-entries.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_post_a_balanced_journal_entry(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        [$cash, $equity] = $this->makeAccounts($company);

        $response = $this->actingAs($admin)->post(route('journal-entries.store'), [
            'entry_date' => now()->toDateString(),
            'description' => 'Opening balance',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $equity->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('journal_entries', ['company_id' => $company->id, 'description' => 'Opening balance', 'created_by_id' => $admin->id]);
    }

    public function test_posting_an_unbalanced_journal_entry_fails_validation(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        [$cash, $equity] = $this->makeAccounts($company);

        $response = $this->actingAs($admin)->post(route('journal-entries.store'), [
            'entry_date' => now()->toDateString(),
            'description' => 'Unbalanced',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $equity->id, 'debit' => 0, 'credit' => 900],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
    }

    public function test_a_line_that_is_both_a_debit_and_a_credit_fails_validation(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        [$cash, $equity] = $this->makeAccounts($company);

        $response = $this->actingAs($admin)->post(route('journal-entries.store'), [
            'entry_date' => now()->toDateString(),
            'description' => 'Bad line',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 1000],
                ['account_id' => $equity->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('lines.0');
    }

    public function test_a_company_admin_cannot_view_another_companys_entry(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherEntry = JournalEntry::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('journal-entries.show', $otherEntry))->assertForbidden();
    }

    public function test_voiding_a_journal_entry(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        [$cash, $equity] = $this->makeAccounts($company);
        $entry = JournalEntry::factory()->create(['company_id' => $company->id]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 500, 'credit' => 0]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $equity->id, 'debit' => 0, 'credit' => 500]);

        $response = $this->actingAs($admin)->post(route('journal-entries.void', $entry), ['reason' => 'Mistake']);

        $response->assertRedirect(route('journal-entries.show', $entry));
        $this->assertSame('voided', $entry->fresh()->status);
    }
}
