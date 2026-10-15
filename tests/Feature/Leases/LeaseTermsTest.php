<?php

namespace Tests\Feature\Leases;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Support\RentPeriods;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Units are priced per month / quarter / half-year / year; a lease bills
 * that price converted to its own cycle, ends after N periods, and shows
 * its whole value and balance. Landlords can rent out their own units.
 */
class LeaseTermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rent_periods_convert_and_end_dates_follow_the_cycle(): void
    {
        $this->assertSame(1500000.0, RentPeriods::convert(500000, 'monthly', 'quarterly'));
        $this->assertSame(500000.0, RentPeriods::convert(6000000, 'annual', 'monthly'));
        $this->assertSame(3000000.0, RentPeriods::convert(1500000, 'quarterly', 'semi_annual'));

        $start = Carbon::parse('2026-01-15');
        $this->assertSame('2027-01-14', RentPeriods::endDate($start, 'monthly', 12)->toDateString());
        $this->assertSame('2026-04-14', RentPeriods::endDate($start, 'quarterly')->toDateString());
        $this->assertSame('2026-07-14', RentPeriods::endDate($start, 'semi_annual')->toDateString());
        $this->assertSame(4, RentPeriods::periodsBetween($start, Carbon::parse('2027-01-14'), 'quarterly'));
    }

    public function test_a_new_lease_fills_rent_and_end_date_and_reports_its_balance(): void
    {
        $company = Company::factory()->create();
        $staff = PermissionsTeam::runAs($company->id, function () use ($company) {
            Role::findOrCreate('Admin', 'web')->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));
            $user = User::factory()->create(['company_id' => $company->id]);
            $user->assignRole('Admin');

            return $user;
        });
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => Property::factory()->create(['company_id' => $company->id])->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'rent_amount' => 500000, 'rent_period' => 'monthly']);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        $this->actingAs($staff)->get(route('leases.create'))->assertOk()->assertSee('name="periods"', false)->assertSee('data-rent-period="monthly"', false);

        // Web: quarterly billing, 4 quarters, rent and end date left blank.
        $this->actingAs($staff)->post(route('leases.store'), [
            'tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'billing_cycle' => 'quarterly',
            'start_date' => '2026-01-15', 'periods' => 4,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $lease = Lease::firstOrFail();
        $this->assertEquals(1500000, $lease->rent_amount, '500,000 a month = 1,500,000 a quarter');
        $this->assertSame('2027-01-14', $lease->end_date->toDateString());
        $this->assertSame(4, $lease->periodsCount());
        $this->assertEquals(6000000, $lease->contractValue());

        $this->actingAs($staff)->get(route('leases.show', $lease))->assertOk()->assertSee('6,000,000.00');

        // API: an annual-priced unit billed monthly.
        $yearly = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'rent_amount' => 12000000, 'rent_period' => 'annual']);
        $other = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        Sanctum::actingAs($staff);
        $response = $this->postJson('/api/v1/leases', [
            'tenant_id' => $other->uuid, 'unit_id' => $yearly->uuid, 'billing_cycle' => 'monthly', 'start_date' => '2026-02-01', 'periods' => 12,
        ])->assertCreated();
        $this->assertEquals(1000000, $response->json('data.rent_amount'));
        $this->assertSame('2027-01-31', $response->json('data.end_date'));
        $this->assertEquals(12000000, $response->json('data.contract_value'));
        $this->assertSame(12, $response->json('data.periods'));

        // Typed values win.
        $third = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'rent_amount' => 400000]);
        $this->postJson('/api/v1/leases', [
            'tenant_id' => Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active'])->uuid, 'unit_id' => $third->uuid,
            'billing_cycle' => 'monthly', 'start_date' => '2026-03-01', 'end_date' => '2026-12-31', 'rent_amount' => 350000,
        ])->assertCreated()->assertJsonPath('data.end_date', '2026-12-31')->assertJsonPath('data.rent_amount', 350000);

        // Units remember what their price is per.
        $this->getJson("/api/v1/units/{$yearly->uuid}")->assertOk()->assertJsonPath('data.rent_period', 'annual');
    }

    public function test_landlords_rent_out_their_own_units_only(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'username' => 'juma.l']);
        $landlord->forceFill(['password' => 'Landlord#2026'])->save();
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id])->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'rent_amount' => 3000000, 'rent_period' => 'semi_annual']);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active', 'landlord_id' => $landlord->id]);
        $notMyTenant = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $otherUnit = Unit::factory()->create(['company_id' => $company->id, 'status' => 'vacant',
            'building_id' => Building::factory()->create(['company_id' => $company->id, 'property_id' => Property::factory()->create(['company_id' => $company->id])->id])->id]);

        $this->actingAs($landlord, 'landlord');
        $this->get(route('landlord-portal.properties.show', $building->property))->assertOk()
            ->assertSee(route('landlord-portal.leases.create', ['unit' => $unit->uuid]), false);
        $this->get(route('landlord-portal.leases.create', ['unit' => $unit->uuid]))->assertOk()->assertSee('data-rent-period="semi_annual"', false);

        $this->post(route('landlord-portal.leases.store'), ['tenant_id' => $notMyTenant->id, 'unit_id' => $unit->id, 'start_date' => '2026-11-01'])->assertSessionHasErrors('tenant_id');
        $this->post(route('landlord-portal.leases.store'), ['tenant_id' => $tenant->id, 'unit_id' => $otherUnit->id, 'start_date' => '2026-11-01'])->assertSessionHasErrors('unit_id');

        $this->post(route('landlord-portal.leases.store'), ['tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'billing_cycle' => 'monthly', 'start_date' => '2026-11-01', 'periods' => 6])
            ->assertSessionHasNoErrors()->assertRedirect();
        $lease = Lease::where('unit_id', $unit->id)->firstOrFail();
        $this->assertEquals(500000, $lease->rent_amount, '3,000,000 per half-year = 500,000 a month');
        $this->assertSame('2027-04-30', $lease->end_date->toDateString());
        $this->assertSame('occupied', $unit->fresh()->status);
        $this->assertNull($lease->invoices()->first()?->payments()->first()?->recorded_by_id);

        // App API.
        auth('landlord')->logout();
        $token = $this->postJson('/api/v1/portal/landlord/login', ['email' => 'juma.l', 'password' => 'Landlord#2026'])->json('token');
        $free = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'rent_amount' => 200000]);
        $options = $this->withToken($token)->getJson('/api/v1/portal/landlord/lease-options')->assertOk();
        $this->assertSame([$free->uuid], collect($options->json('units'))->pluck('uuid')->all());
        $this->withToken($token)->postJson('/api/v1/portal/landlord/leases', ['tenant_id' => $tenant->uuid, 'unit_id' => $free->uuid, 'billing_cycle' => 'quarterly', 'start_date' => '2026-11-01'])
            ->assertCreated()->assertJsonPath('data.rent_amount', 600000)->assertJsonPath('data.end_date', '2027-01-31');
    }

    public function test_ownership_checks_work_when_the_database_returns_ids_as_text(): void
    {
        $this->assertTrue(same_id('5', 5));
        $this->assertTrue(same_id(5, '5'));
        $this->assertFalse(same_id('5', 6));
        $this->assertFalse(same_id(null, null));
        $this->assertFalse(same_id(null, 0));
    }

    public function test_the_super_admin_signs_in_to_the_app_and_creates_companies(): void
    {
        User::factory()->create(['company_id' => null, 'username' => 'super.admin', 'password' => 'Super#2026']);

        $login = $this->postJson('/api/v1/auth/login', ['email' => 'super.admin', 'password' => 'Super#2026'])->assertOk();
        $this->assertSame(['companies.viewAny', 'companies.create', 'companies.update'], $login->json('permissions'));
        $this->assertTrue($login->json('user.is_saas_owner'));
        $token = $login->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('permissions.0', 'companies.viewAny');
        $created = $this->withToken($token)->postJson('/api/v1/companies', ['name' => 'Kilimanjaro Homes', 'admin_name' => 'Asha Admin', 'admin_email' => 'asha@kili.test'])
            ->assertCreated();
        $this->assertNotEmpty($created->json('admin.username'));
        $this->withToken($token)->getJson('/api/v1/companies')->assertOk()->assertJsonPath('data.0.name', 'Kilimanjaro Homes');
        $this->withToken($token)->putJson('/api/v1/companies/'.$created->json('data.uuid'), ['name' => 'Kilimanjaro Homes', 'status' => 'suspended'])
            ->assertOk()->assertJsonPath('data.status', 'suspended');
    }
}
