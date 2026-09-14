<?php

namespace Tests\Feature\Accounting;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryPolicyTest extends TestCase
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

    public function test_saas_owner_cannot_manage_journal_entries(): void
    {
        $owner = User::factory()->create();
        $entry = JournalEntry::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($owner->can('viewAny', JournalEntry::class));
        $this->assertFalse($owner->can('view', $entry));
        $this->assertFalse($owner->can('create', JournalEntry::class));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_entries(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['journal-entries.viewAny', 'journal-entries.view', 'journal-entries.create', 'journal-entries.void']);
        $entry = JournalEntry::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $entry): void {
            $this->assertTrue($user->can('viewAny', JournalEntry::class));
            $this->assertTrue($user->can('view', $entry));
            $this->assertTrue($user->can('create', JournalEntry::class));
            $this->assertTrue($user->can('void', $entry));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['journal-entries.view', 'journal-entries.void']);
        $otherEntry = JournalEntry::factory()->create(['company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherEntry): void {
            $this->assertFalse($user->can('view', $otherEntry));
            $this->assertFalse($user->can('void', $otherEntry));
        });
    }
}
