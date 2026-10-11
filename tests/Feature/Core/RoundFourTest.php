<?php

namespace Tests\Feature\Core;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Users\Notifications\AccessChanged;
use App\Modules\Users\Services\RoleService;
use App\Modules\Users\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Access-change notifications, sidebar visibility, the property form,
 * portal breadcrumbs, report status filters and signing in from any
 * sign-in page.
 */
class RoundFourTest extends TestCase
{
    use RefreshDatabase;

    protected function staff(Company $company, array $permissions, string $role = 'Clerk'): User
    {
        return PermissionsTeam::runAs($company->id, function () use ($company, $permissions, $role) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
            $user = User::factory()->create(['company_id' => $company->id]);
            $user->assignRole($role);

            return $user;
        });
    }

    public function test_changing_someones_roles_notifies_them_with_what_they_can_now_do(): void
    {
        $company = Company::factory()->create();
        $user = $this->staff($company, ['tenants.viewAny']);
        PermissionsTeam::runAs($company->id, fn () => Role::findOrCreate('Accountant', 'web')->syncPermissions(['invoices.viewAny', 'invoices.create']));

        app(UserService::class)->syncRoles($user, ['Accountant']);

        $notification = $user->fresh()->notifications()->firstOrFail();
        $this->assertSame(AccessChanged::class, $notification->type);
        $this->assertSame(['invoices.create', 'invoices.viewAny'], collect($notification->data['granted'])->sort()->values()->all());
        $this->assertSame(['tenants.viewAny'], $notification->data['revoked']);
        $this->assertStringContainsString('Invoices: create', $notification->data['message']);
    }

    public function test_editing_a_roles_permissions_notifies_everyone_who_holds_it(): void
    {
        $company = Company::factory()->create();
        $a = $this->staff($company, ['tenants.viewAny'], 'Front Desk');
        $b = $this->staff($company, ['tenants.viewAny'], 'Front Desk');
        $outsider = $this->staff($company, ['tenants.viewAny'], 'Other');
        $role = PermissionsTeam::runAs($company->id, fn () => Role::findByName('Front Desk', 'web'));

        app(RoleService::class)->update($role, ['name' => 'Front Desk', 'permissions' => ['tenants.viewAny', 'tenants.create']]);

        $this->assertSame(1, $a->fresh()->notifications()->count());
        $this->assertSame(1, $b->fresh()->notifications()->count());
        $this->assertSame(0, $outsider->fresh()->notifications()->count());
        $this->assertSame(['tenants.create'], $a->fresh()->notifications()->first()->data['granted']);
    }

    public function test_saving_a_role_without_changes_sends_nothing(): void
    {
        $company = Company::factory()->create();
        $user = $this->staff($company, ['tenants.viewAny'], 'Same');
        $role = PermissionsTeam::runAs($company->id, fn () => Role::findByName('Same', 'web'));

        app(RoleService::class)->update($role, ['name' => 'Same', 'permissions' => ['tenants.viewAny']]);

        $this->assertSame(0, $user->fresh()->notifications()->count());
    }

    public function test_only_the_super_admin_sees_companies_in_the_sidebar(): void
    {
        $company = Company::factory()->create();
        $admin = $this->staff($company, ['tenants.viewAny']);
        $owner = User::factory()->create(['company_id' => null]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee(route('companies.index'), false);
        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertSee(route('companies.index'), false);
    }

    public function test_the_new_property_form_has_no_coordinates_but_editing_keeps_them(): void
    {
        $company = Company::factory()->create();
        $admin = $this->staff($company, ['properties.viewAny', 'properties.view', 'properties.create', 'properties.update']);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->get(route('properties.create'))->assertOk()->assertDontSee('name="latitude"', false);
        $this->actingAs($admin)->get(route('properties.edit', $property))->assertOk()->assertSee('name="latitude"', false);
        $this->actingAs($admin)->post(route('properties.store'), ['name' => 'No Coordinates', 'type' => 'residential'])
            ->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_portal_pages_show_breadcrumbs(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);

        $this->actingAs($tenant, 'tenant')->get(route('portal.invoices.index'))->assertOk()
            ->assertSee('aria-label="breadcrumb"', false)
            ->assertSee(route('portal.dashboard'), false);

        // Not on the dashboard itself.
        $this->actingAs($tenant, 'tenant')->get(route('portal.dashboard'))->assertOk()
            ->assertDontSee('aria-label="breadcrumb"', false);
    }

    public function test_reports_filter_by_status_on_the_page_and_in_exports(): void
    {
        $company = Company::factory()->create();
        $admin = $this->staff($company, ['reports.view']);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'unit_number' => 'VAC-1']);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied', 'unit_number' => 'OCC-1']);

        $this->actingAs($admin)->get(route('reports.rent-roll', ['status' => 'vacant']))->assertOk()
            ->assertSee('VAC-1')->assertDontSee('OCC-1')
            ->assertSee(route('reports.rent-roll.export', ['format' => 'csv', 'status' => 'vacant']), false);

        $csv = $this->actingAs($admin)->get(route('reports.rent-roll.export', ['format' => 'csv', 'status' => 'occupied']));
        $csv->assertOk();
        $body = $csv->baseResponse instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            ? file_get_contents($csv->baseResponse->getFile()->getPathname())
            : $csv->streamedContent();
        $this->assertStringContainsString('OCC-1', $body);
        $this->assertStringNotContainsString('VAC-1', $body);

        $this->actingAs($admin)->get(route('reports.occupancy', ['status' => 'archived']))->assertOk();
        $this->actingAs($admin)->get(route('reports.ar-aging', ['status' => 'overdue']))->assertOk();
    }

    public function test_anyone_can_sign_in_from_any_sign_in_page(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->create(['company_id' => $company->id, 'username' => 'desk.staff', 'password' => 'Staff#2026']);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'username' => 'amina.t']);
        $tenant->forceFill(['password' => 'Tenant#2026'])->save();
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'username' => 'juma.l']);
        $landlord->forceFill(['password' => 'Landlord#2026'])->save();

        // Staff on the tenant portal page -> staff dashboard.
        $this->post(route('portal.login'), ['email' => 'desk.staff', 'password' => 'Staff#2026'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($staff, 'web');
        $this->flushSession();
        auth('web')->logout();

        // Tenant on the staff page -> tenant portal.
        $this->post(route('login'), ['email' => 'amina.t', 'password' => 'Tenant#2026'])->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($tenant, 'tenant');
        auth('tenant')->logout();

        // Landlord on the tenant page -> landlord portal.
        $this->post(route('portal.login'), ['email' => 'juma.l', 'password' => 'Landlord#2026'])->assertRedirect(route('landlord-portal.dashboard'));
        $this->assertAuthenticatedAs($landlord, 'landlord');
        auth('landlord')->logout();

        // Wrong details still refused everywhere.
        $this->from(route('login'))->post(route('login'), ['email' => 'desk.staff', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }
}
