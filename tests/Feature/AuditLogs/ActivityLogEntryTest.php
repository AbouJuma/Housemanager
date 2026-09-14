<?php

namespace Tests\Feature\AuditLogs;

use App\Core\Tenancy\CompanyContext;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_update_is_stamped_with_its_own_id(): void
    {
        $company = Company::factory()->create();

        $company->update(['name' => 'Renamed Co']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Company::class,
            'subject_id' => $company->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_a_company_users_update_is_stamped_with_their_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $user->update(['name' => 'Renamed User']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_a_saas_owner_users_update_is_not_incorrectly_attributed_to_the_acting_users_company(): void
    {
        // Created first, before any company is in context: otherwise
        // BelongsToCompany's own auto-stamp-on-create would assign this
        // "SaaS owner" a company_id, which isn't the scenario under test.
        $saasOwner = User::factory()->create(); // company_id null
        $this->assertNull($saasOwner->company_id);

        $actingCompany = Company::factory()->create();
        app(CompanyContext::class)->set($actingCompany->id);

        $saasOwner->update(['name' => 'Renamed Owner']);

        // The subject (SaaS owner) has no company - the log must reflect
        // that, not borrow whichever company the acting request happens
        // to be scoped to.
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $saasOwner->id,
            'event' => 'updated',
            'company_id' => null,
        ]);
    }

    public function test_a_saas_owner_editing_a_specific_companys_data_is_attributed_to_that_company(): void
    {
        // Acting user has no company of their own (SaaS owner), but the
        // subject being edited belongs to a real company - the log must
        // follow the subject, not the (absent) acting company.
        app(CompanyContext::class)->clear();

        $company = Company::factory()->create();
        $company->update(['name' => 'Edited By SaaS Owner']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Company::class,
            'subject_id' => $company->id,
            'company_id' => $company->id,
        ]);
    }
}
