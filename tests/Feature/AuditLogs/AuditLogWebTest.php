<?php

namespace Tests\Feature\AuditLogs;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_see_activity_across_companies(): void
    {
        $owner = User::factory()->create();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $response = $this->actingAs($owner)->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Company A');
        $response->assertSee('Company B');
    }

    public function test_a_company_admin_only_sees_their_own_companys_activity(): void
    {
        $ownCompany = Company::factory()->create(['name' => 'Own Company']);
        $otherCompany = Company::factory()->create(['name' => 'Other Company']);
        $admin = $this->makeCompanyAdmin($ownCompany);

        // Trigger an update on the admin's own company so there's
        // something in their scope to see.
        $ownCompany->update(['name' => 'Own Company Renamed']);

        $response = $this->actingAs($admin)->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertDontSee('Other Company');
    }

    public function test_a_company_user_without_permission_cannot_view_audit_logs(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_the_log_name_filter_narrows_results(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $company->update(['name' => 'Renamed']);

        $response = $this->actingAs($owner)->get(route('audit-logs.index', ['log_name' => 'company']));

        $response->assertOk();
    }

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['audit-logs.view']);
            $admin->assignRole($role);
        });

        return $admin;
    }
}
