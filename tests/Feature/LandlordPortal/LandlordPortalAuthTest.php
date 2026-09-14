<?php

namespace Tests\Feature\LandlordPortal;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\LandlordPortal\Services\LandlordPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LandlordPortalAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function makePortalLandlord(Company $company, string $password = 'correct-password'): Landlord
    {
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'email' => 'landlord@example.test']);
        $landlord->forceFill(['password' => $password])->save();

        return $landlord;
    }

    public function test_a_guest_hitting_a_protected_portal_route_is_redirected_to_the_landlord_portal_login(): void
    {
        $response = $this->get(route('landlord-portal.dashboard'));

        $response->assertRedirect(route('landlord-portal.login'));
    }

    public function test_a_landlord_can_log_in_with_correct_credentials(): void
    {
        $company = Company::factory()->create();
        $landlord = $this->makePortalLandlord($company);

        $response = $this->post(route('landlord-portal.login'), [
            'email' => 'landlord@example.test',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('landlord-portal.dashboard'));
        $this->assertTrue(Auth::guard('landlord')->check());
        $this->assertSame($landlord->id, Auth::guard('landlord')->id());
    }

    public function test_login_fails_with_the_wrong_password(): void
    {
        $company = Company::factory()->create();
        $this->makePortalLandlord($company);

        $response = $this->post(route('landlord-portal.login'), [
            'email' => 'landlord@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('landlord')->check());
    }

    public function test_login_fails_for_a_landlord_without_portal_access(): void
    {
        $company = Company::factory()->create();
        Landlord::factory()->create(['company_id' => $company->id, 'email' => 'no-access@example.test']);

        $response = $this->post(route('landlord-portal.login'), [
            'email' => 'no-access@example.test',
            'password' => 'anything',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('landlord')->check());
    }

    public function test_logging_in_correctly_scopes_the_company_context(): void
    {
        $company = Company::factory()->create();
        $this->makePortalLandlord($company);

        $this->post(route('landlord-portal.login'), [
            'email' => 'landlord@example.test',
            'password' => 'correct-password',
        ]);

        $response = $this->get(route('landlord-portal.dashboard'));

        $response->assertOk();
    }

    public function test_an_already_logged_in_landlord_visiting_login_is_redirected_to_the_dashboard(): void
    {
        $company = Company::factory()->create();
        $landlord = $this->makePortalLandlord($company);

        $response = $this->actingAs($landlord, 'landlord')->get(route('landlord-portal.login'));

        $response->assertRedirect(route('landlord-portal.dashboard'));
    }

    public function test_logout_ends_the_portal_session(): void
    {
        $company = Company::factory()->create();
        $landlord = $this->makePortalLandlord($company);

        $response = $this->actingAs($landlord, 'landlord')->post(route('landlord-portal.logout'));

        $response->assertRedirect(route('landlord-portal.login'));
        $this->assertFalse(Auth::guard('landlord')->check());
    }

    public function test_enable_access_via_the_service_produces_a_working_login(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'email' => 'service@example.test']);
        $plainPassword = app(LandlordPortalService::class)->enableAccess($landlord);

        $response = $this->post(route('landlord-portal.login'), [
            'email' => 'service@example.test',
            'password' => $plainPassword,
        ]);

        $response->assertRedirect(route('landlord-portal.dashboard'));
    }

    public function test_a_tenant_portal_session_does_not_grant_landlord_portal_access(): void
    {
        $company = Company::factory()->create();
        $tenant = \App\Modules\Tenants\Models\Tenant::factory()->create(['company_id' => $company->id]);
        $tenant->forceFill(['password' => 'tenant-password'])->save();

        $response = $this->actingAs($tenant, 'tenant')->get(route('landlord-portal.dashboard'));

        $response->assertRedirect(route('landlord-portal.login'));
    }
}
