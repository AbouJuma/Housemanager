<?php

namespace Tests\Feature\Companies;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id, 'name' => 'Amina Mushi']);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Role '.uniqid(), 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_a_company_admin_can_update_their_company_details(): void
    {
        $company = Company::factory()->create(['name' => 'Old Name']);
        $admin = $this->makeUser($company, ['settings.view', 'settings.update']);

        $this->actingAs($admin)->get(route('company-profile.edit'))->assertOk()->assertSee('Company Profile');

        $this->actingAs($admin)->put(route('company-profile.update'), [
            'name' => 'Pwani Homes Ltd',
            'email' => 'info@pwanihomes.co.tz',
            'phone' => '+255 712 345 678',
            'website' => 'https://pwanihomes.co.tz',
            'city' => 'Kinondoni',
            'state' => 'Dar es Salaam',
            'country_code' => 'TZ',
            'timezone' => 'Africa/Dar_es_Salaam',
            'currency_code' => 'TZS',
        ])->assertRedirect(route('company-profile.edit'))->assertSessionHas('success');

        $company->refresh();
        $this->assertSame('Pwani Homes Ltd', $company->name);
        $this->assertSame('TZS', $company->currency_code);
        $this->assertSame('Africa/Dar_es_Salaam', $company->timezone);
    }

    public function test_status_and_slug_cannot_be_changed_from_the_profile_page(): void
    {
        $company = Company::factory()->create(['status' => 'active']);
        $admin = $this->makeUser($company, ['settings.view', 'settings.update']);
        $slug = $company->slug;

        $this->actingAs($admin)->put(route('company-profile.update'), [
            'name' => 'Renamed',
            'timezone' => 'Africa/Dar_es_Salaam',
            'currency_code' => 'TZS',
            'status' => 'cancelled',
            'slug' => 'hijacked',
        ]);

        $company->refresh();
        $this->assertSame('active', $company->status);
        $this->assertSame($slug, $company->slug);
    }

    public function test_staff_without_update_permission_cannot_change_details(): void
    {
        $company = Company::factory()->create(['name' => 'Keep Me']);
        $staff = $this->makeUser($company, ['settings.view']);

        $this->actingAs($staff)->put(route('company-profile.update'), [
            'name' => 'Changed',
            'timezone' => 'Africa/Dar_es_Salaam',
            'currency_code' => 'TZS',
        ])->assertForbidden();

        $this->assertSame('Keep Me', $company->fresh()->name);
    }

    public function test_validation_errors_are_flashed_for_the_toast(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeUser($company, ['settings.view', 'settings.update']);

        $this->actingAs($admin)
            ->from(route('company-profile.edit'))
            ->put(route('company-profile.update'), ['name' => '', 'timezone' => 'Mars/Base', 'currency_code' => 'TZS'])
            ->assertSessionHasErrors(['name', 'timezone']);

        // The redirected page hands the errors to the toast script.
        $this->actingAs($admin)->get(route('company-profile.edit'))
            ->assertOk();
        $this->actingAs($admin)
            ->withSession(['success' => 'Company details updated.'])
            ->get(route('company-profile.edit'))
            ->assertSee('window.__flashToasts', false)
            ->assertSee('Company details updated.');
    }

    public function test_the_dashboard_greets_in_the_companys_local_time(): void
    {
        // 11:30 UTC is 14:30 in Dar es Salaam (UTC+3) - afternoon there.
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:30:00', 'UTC'));

        $company = Company::factory()->create(['timezone' => 'Africa/Dar_es_Salaam']);
        $user = $this->makeUser($company, []);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Good afternoon, Amina')
            ->assertDontSee('Good morning');

        Carbon::setTestNow();
    }

    public function test_money_fields_typed_with_thousand_separators_are_accepted(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeUser($company, ['leases.create', 'leases.view', 'leases.viewAny']);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant']);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        $this->actingAs($admin)->post(route('leases.store'), [
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'billing_cycle' => 'monthly',
            'rent_amount' => '1,250,000.50',
            'deposit_amount' => '2,500,000',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('leases', ['unit_id' => $unit->id, 'rent_amount' => 1250000.50, 'deposit_amount' => 2500000]);
    }
}
