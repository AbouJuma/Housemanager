<?php

namespace Tests\Feature\Dashboard;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionInvoiceService;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_sees_platform_wide_kpis(): void
    {
        $owner = User::factory()->create();
        Company::factory()->count(2)->active()->create();
        Company::factory()->count(1)->create(); // trial

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('isSaasOwner', true);
        $response->assertViewHas('totalCompanies', 3);
        $response->assertViewHas('activeCompanies', 2);
        $response->assertViewHas('trialCompanies', 1);
    }

    /**
     * phpunit.xml forces BEEMS_API_KEY/SECRET_KEY empty for every test
     * (see its own comment - avoids a real Beem API call from the
     * platform-fallback SMS path during automated tests), so
     * SmsManager::isPlatformFallbackAvailable() is false here and
     * getPlatformSmsBalance() short-circuits without any network call -
     * this asserts that failure path renders instead of throwing.
     */
    public function test_saas_owner_dashboard_reports_sms_balance_unavailable_without_credentials(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('smsBalance', fn ($smsBalance) => $smsBalance['success'] === false && $smsBalance['balance'] === null);
    }

    public function test_company_user_sees_only_their_own_companys_kpis(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $ownCompany->id]);
        User::factory()->count(2)->create(['company_id' => $ownCompany->id]);
        User::factory()->count(5)->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('isSaasOwner', false);
        // The acting user + 2 more in their own company; the other
        // company's 5 users must never leak into this count.
        $response->assertViewHas('totalUsers', 3);
    }

    public function test_company_users_dashboard_reflects_their_role_breakdown(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company->id]);
        $staffUser = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin, $staffUser): void {
            $admin->assignRole(Role::findOrCreate('Admin', 'web'));
            $staffUser->assignRole(Role::findOrCreate('Staff', 'web'));
        });

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('usersByRole', ['Admin' => 1, 'Staff' => 1]);
    }

    public function test_company_users_dashboard_reflects_property_and_occupancy_kpis(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $ownCompany->id]);

        $property = Property::factory()->create(['company_id' => $ownCompany->id]);
        $building = Building::factory()->create(['company_id' => $ownCompany->id, 'property_id' => $property->id]);
        Unit::factory()->occupied()->create(['company_id' => $ownCompany->id, 'building_id' => $building->id]);
        Unit::factory()->occupied()->create(['company_id' => $ownCompany->id, 'building_id' => $building->id]);
        Unit::factory()->vacant()->create(['company_id' => $ownCompany->id, 'building_id' => $building->id]);

        // Another company's portfolio must never leak into these counts.
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);
        Unit::factory()->count(10)->create(['company_id' => $otherCompany->id, 'building_id' => $otherBuilding->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalProperties', 1);
        $response->assertViewHas('totalUnits', 3);
        $response->assertViewHas('occupiedUnits', 2);
        $response->assertViewHas('occupancyRate', 67);
    }

    public function test_occupancy_rate_is_null_when_the_company_has_no_units(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('occupancyRate', null);
    }

    public function test_saas_owner_sees_subscription_kpis(): void
    {
        $owner = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['price' => 100, 'billing_cycle' => 'monthly']);
        $company = Company::factory()->create();
        $subscription = app(SubscriptionService::class)->subscribe($company, $plan, 'active');
        app(SubscriptionInvoiceService::class)->generate($subscription);

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('activeSubscriptionsCount', 1);
        $response->assertViewHas('mrr', 100.0);
        $response->assertViewHas('unpaidInvoicesCount', 1);
        $response->assertViewHas('unpaidInvoicesTotal', 100.0);
        $response->assertSee('Active Subscriptions');
        $response->assertSee('MRR');
    }

    public function test_recent_activity_is_scoped_to_the_viewers_company(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $ownCompany->id]);

        $ownCompany->update(['name' => 'Renamed']);
        $otherCompany->update(['name' => 'Renamed']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $recentActivity = $response->viewData('recentActivity');
        $this->assertNotEmpty($recentActivity);
        $this->assertTrue($recentActivity->every(fn ($entry) => $entry->company_id === $ownCompany->id));
    }
}
