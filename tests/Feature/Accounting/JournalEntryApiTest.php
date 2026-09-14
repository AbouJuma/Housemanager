<?php

namespace Tests\Feature\Accounting;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryApiTest extends TestCase
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

    public function test_a_company_admin_can_post_a_journal_entry_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $cash = Account::factory()->create(['company_id' => $company->id, 'type' => 'asset']);
        $equity = Account::factory()->create(['company_id' => $company->id, 'type' => 'equity']);

        $response = $this->postJson('/api/v1/journal-entries', [
            'entry_date' => now()->toDateString(),
            'description' => 'Opening balance',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $equity->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'posted');
    }

    public function test_a_company_admin_cannot_view_another_companys_entry_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherEntry = JournalEntry::factory()->create(['company_id' => $otherCompany->id]);

        $this->getJson("/api/v1/journal-entries/{$otherEntry->uuid}")->assertForbidden();
    }

    public function test_voiding_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $cash = Account::factory()->create(['company_id' => $company->id]);
        $equity = Account::factory()->create(['company_id' => $company->id]);
        $entry = JournalEntry::factory()->create(['company_id' => $company->id]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 500, 'credit' => 0]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $equity->id, 'debit' => 0, 'credit' => 500]);

        $response = $this->postJson("/api/v1/journal-entries/{$entry->uuid}/void");

        $response->assertOk()->assertJsonPath('data.status', 'voided');
    }
}
