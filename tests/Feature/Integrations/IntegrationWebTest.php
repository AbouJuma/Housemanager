<?php

namespace Tests\Feature\Integrations;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntegrationWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_cannot_access_the_integrations_hub(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('integrations.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_view_the_integrations_hub(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->get(route('integrations.index'));

        $response->assertOk();
        $response->assertSee('Stripe');
        $response->assertSee('Payments');
    }

    public function test_a_company_admin_can_view_and_save_a_providers_credentials(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('integrations.edit', ['payment', 'stripe']))->assertOk();

        $response = $this->actingAs($admin)->put(route('integrations.update', ['payment', 'stripe']), [
            'mode' => 'sandbox',
            'is_active' => '1',
            'api_key' => 'pk_test_123',
        ]);

        $response->assertRedirect(route('integrations.edit', ['payment', 'stripe']));
        $this->assertDatabaseHas('integrations', [
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'is_active' => true,
        ]);
    }

    public function test_a_company_user_without_permission_cannot_access_integrations(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('integrations.index'))->assertForbidden();
    }

    public function test_testing_an_unconfigured_integration_prompts_to_save_first(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('integrations.test', ['payment', 'stripe']));

        $response->assertRedirect(route('integrations.edit', ['payment', 'stripe']));
        $response->assertSessionHas('error');
    }

    public function test_testing_a_saved_integration_with_no_driver_reports_that_honestly(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        // 'maps'/'google_maps' is genuinely untested-yet - the Maps
        // module (Phase E) hasn't been built. By the end of Module 11
        // every Storage/Email/SMS/Payment provider has a real tester,
        // so this can no longer use a payment provider as its example
        // without risking an actual unfaked HTTP call.
        Integration::create(['company_id' => $company->id, 'type' => 'maps', 'provider' => 'google_maps']);

        $response = $this->actingAs($admin)->post(route('integrations.test', ['maps', 'google_maps']));

        $response->assertRedirect(route('integrations.edit', ['maps', 'google_maps']));
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'No connection tester',
            session('error')
        );
    }

    public function test_testing_a_saved_ai_integration_with_no_driver_reports_that_honestly(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        Integration::create(['company_id' => $company->id, 'type' => 'ai', 'provider' => 'openai']);

        $response = $this->actingAs($admin)->post(route('integrations.test', ['ai', 'openai']));

        $response->assertRedirect(route('integrations.edit', ['ai', 'openai']));
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'No connection tester',
            session('error')
        );
    }

    public function test_a_company_admin_cannot_reach_another_companys_integration_by_guessing_the_url(): void
    {
        // The route shape is type/provider (not a per-company id), so
        // this proves the controller resolves strictly within the
        // acting user's own company rather than any shared row.
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($ownCompany);
        Integration::create([
            'company_id' => $otherCompany->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'credentials' => ['api_key' => 'other-companys-secret'],
        ]);

        $response = $this->actingAs($admin)->get(route('integrations.edit', ['payment', 'stripe']));

        $response->assertOk();
        $response->assertDontSee('other-companys-secret');
    }

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['integrations.view', 'integrations.update']);
            $admin->assignRole($role);
        });

        return $admin;
    }
}
