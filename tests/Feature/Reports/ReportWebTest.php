<?php

namespace Tests\Feature\Reports;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['reports.view']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_saas_owner_can_access_reports(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('reports.index'))->assertOk();
    }

    public function test_a_company_user_without_the_permission_cannot_access_reports(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_view_the_reports_hub(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    }

    public function test_each_report_page_renders(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('reports.rent-roll'))->assertOk();
        $this->actingAs($admin)->get(route('reports.occupancy'))->assertOk();
        $this->actingAs($admin)->get(route('reports.income-statement'))->assertOk();
        $this->actingAs($admin)->get(route('reports.ar-aging'))->assertOk();
    }

    public function test_saas_owner_can_access_reports_pages(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('reports.subscription-status'))->assertOk();
        $this->actingAs($owner)->get(route('reports.billing-status'))->assertOk();
    }

    public function test_company_admin_cannot_access_subscription_or_billing_status_reports(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('reports.subscription-status'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.billing-status'))->assertForbidden();
    }

    public function test_subscription_status_and_billing_status_pages_render_for_saas_owner(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('reports.subscription-status'))
            ->assertOk()
            ->assertSee('Subscription Status');

        $this->actingAs($owner)->get(route('reports.billing-status'))
            ->assertOk()
            ->assertSee('Billing Status');
    }

    public function test_each_report_exports_in_every_supported_format(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        foreach (['rent-roll', 'occupancy', 'income-statement', 'ar-aging'] as $report) {
            foreach (['pdf', 'xlsx', 'csv'] as $format) {
                $response = $this->actingAs($admin)->get(route("reports.{$report}.export", $format));
                $response->assertOk();
                $response->assertHeader('content-disposition');
            }
        }
    }

    public function test_an_unsupported_export_format_is_rejected(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('reports.rent-roll.export', 'docx'))->assertNotFound();
    }
}
