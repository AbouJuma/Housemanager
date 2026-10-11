<?php

namespace Tests\Feature\MobileApi;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Token issuance for all four mobile-app roles (staff, tenant, landlord,
 * buyer) and the cross-role guard middleware (App\Modules\TenantPortal\
 * Http\Middleware\EnsureTokenIsTenant and its landlord/buyer
 * equivalents) that stops one role's token from being accepted on
 * another role's routes.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_and_logout(): void
    {
        $company = Company::factory()->create();
        $user = \App\Models\User::factory()->create(['company_id' => $company->id, 'password' => Hash::make('password123')]);

        $login = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password123']);
        $login->assertOk()->assertJsonStructure(['token', 'user']);

        $token = $login->json('token');

        $logout = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/auth/logout');
        $logout->assertNoContent();
    }

    public function test_tenant_login_and_dashboard(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $tenant->forceFill(['password' => 'secret123'])->save();

        $login = $this->postJson('/api/v1/portal/tenant/login', ['email' => $tenant->email, 'password' => 'secret123']);
        $login->assertOk()->assertJsonStructure(['token', 'tenant']);

        $token = $login->json('token');

        $dashboard = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/portal/tenant/dashboard');
        $dashboard->assertOk()->assertJsonStructure(['active_leases', 'outstanding_balance']);
    }

    public function test_landlord_login_and_dashboard(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        $landlord->forceFill(['password' => 'secret123'])->save();

        $login = $this->postJson('/api/v1/portal/landlord/login', ['email' => $landlord->email, 'password' => 'secret123']);
        $login->assertOk()->assertJsonStructure(['token', 'landlord']);

        $token = $login->json('token');

        $dashboard = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/portal/landlord/dashboard');
        $dashboard->assertOk()->assertJsonStructure(['unit_counts', 'total_collected']);
    }

    public function test_buyer_login_and_dashboard_and_sale_ownership(): void
    {
        $company = Company::factory()->create();
        // BuyerFactory's email/phone are faker ->optional(), i.e.
        // sometimes null by design (a real buyer might not have one
        // yet) - pinned explicitly here so this test isn't flaky.
        $buyer = Buyer::factory()->create(['company_id' => $company->id, 'email' => 'buyer-auth-test@example.com']);
        $buyer->forceFill(['password' => 'secret123'])->save();

        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id]);
        $sale = Sale::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id, 'new_buyer_id' => $buyer->id]);

        $login = $this->postJson('/api/v1/portal/buyer/login', ['email' => $buyer->email, 'password' => 'secret123']);
        $login->assertOk()->assertJsonStructure(['token', 'buyer']);

        $token = $login->json('token');

        $dashboard = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/portal/buyer/dashboard');
        $dashboard->assertOk()->assertJsonPath('total_purchases', 1);

        $show = $this->withHeader('Authorization', "Bearer {$token}")->getJson("/api/v1/portal/buyer/sales/{$sale->uuid}");
        $show->assertOk()->assertJsonPath('data.uuid', $sale->uuid);
    }

    public function test_a_landlord_token_is_rejected_on_tenant_only_routes(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        $landlord->forceFill(['password' => 'secret123'])->save();

        $login = $this->postJson('/api/v1/portal/landlord/login', ['email' => $landlord->email, 'password' => 'secret123']);
        $token = $login->json('token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/portal/tenant/dashboard');
        $response->assertForbidden();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $tenant->forceFill(['password' => 'secret123'])->save();

        $response = $this->postJson('/api/v1/portal/tenant/login', ['email' => $tenant->email, 'password' => 'wrong']);
        $response->assertUnprocessable();
    }

    /**
     * A SaaS owner (company_id null - App\Models\User::isSaasOwner())
     * has no company for TenantPolicy et al.'s company-scoped checks to
     * pass against, so every screen in the staff mobile app would 403
     * for them despite a "successful" login - reproduced live against a
     * real seeded platform account before this rejection was added to
     * AuthController::login(). Failing clearly at login is much better
     * UX than a token that then 403s everywhere it's used.
     */
    public function test_saas_owner_login_is_rejected(): void
    {
        $owner = \App\Models\User::factory()->create(['company_id' => null, 'password' => Hash::make('password123')]);

        $response = $this->postJson('/api/v1/auth/login', ['email' => $owner->email, 'password' => 'password123']);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.email.0', 'This app is for company staff accounts. Super Admin/platform accounts are not supported here.');
    }
}
