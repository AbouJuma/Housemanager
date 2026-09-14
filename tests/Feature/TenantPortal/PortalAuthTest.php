<?php

namespace Tests\Feature\TenantPortal;

use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\TenantPortal\Services\TenantPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function makePortalTenant(Company $company, string $password = 'correct-password'): Tenant
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => 'tenant@example.test']);
        $tenant->forceFill(['password' => $password])->save();

        return $tenant;
    }

    public function test_a_guest_hitting_a_protected_portal_route_is_redirected_to_the_portal_login_not_staff_login(): void
    {
        $response = $this->get(route('portal.dashboard'));

        $response->assertRedirect(route('portal.login'));
    }

    public function test_a_tenant_can_log_in_with_correct_credentials(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);

        $response = $this->post(route('portal.login'), [
            'email' => 'tenant@example.test',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertTrue(Auth::guard('tenant')->check());
        $this->assertSame($tenant->id, Auth::guard('tenant')->id());
    }

    public function test_login_fails_with_the_wrong_password(): void
    {
        $company = Company::factory()->create();
        $this->makePortalTenant($company);

        $response = $this->post(route('portal.login'), [
            'email' => 'tenant@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('tenant')->check());
    }

    public function test_login_fails_for_a_tenant_without_portal_access(): void
    {
        $company = Company::factory()->create();
        Tenant::factory()->create(['company_id' => $company->id, 'email' => 'no-access@example.test']);

        $response = $this->post(route('portal.login'), [
            'email' => 'no-access@example.test',
            'password' => 'anything',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('tenant')->check());
    }

    public function test_logging_in_correctly_scopes_the_company_context(): void
    {
        $company = Company::factory()->create();
        $this->makePortalTenant($company);

        $this->post(route('portal.login'), [
            'email' => 'tenant@example.test',
            'password' => 'correct-password',
        ]);

        $response = $this->get(route('portal.dashboard'));

        $response->assertOk();
    }

    public function test_an_already_logged_in_tenant_visiting_login_is_redirected_to_the_dashboard(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);

        $response = $this->actingAs($tenant, 'tenant')->get(route('portal.login'));

        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_logout_ends_the_portal_session(): void
    {
        $company = Company::factory()->create();
        $tenant = $this->makePortalTenant($company);

        $response = $this->actingAs($tenant, 'tenant')->post(route('portal.logout'));

        $response->assertRedirect(route('portal.login'));
        $this->assertFalse(Auth::guard('tenant')->check());
    }

    public function test_enable_access_via_the_service_produces_a_working_login(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => 'service@example.test']);
        $plainPassword = app(TenantPortalService::class)->enableAccess($tenant);

        $response = $this->post(route('portal.login'), [
            'email' => 'service@example.test',
            'password' => $plainPassword,
        ]);

        $response->assertRedirect(route('portal.dashboard'));
    }
}
