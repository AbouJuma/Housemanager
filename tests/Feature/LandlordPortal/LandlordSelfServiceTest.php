<?php

namespace Tests\Feature\LandlordPortal;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Models\InvoicePayment;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Users\Notifications\AccessChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Landlords manage their own properties and tenants and see their own
 * profit & loss and unit occupancy - on the portal and in the app - as
 * far as the company's editable "Landlord" role allows.
 */
class LandlordSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Landlord $landlord;

    protected Landlord $otherLandlord;

    protected Property $property;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->landlord = Landlord::factory()->create(['company_id' => $this->company->id, 'username' => 'juma.landlord', 'phone' => '0754000222']);
        $this->landlord->forceFill(['password' => 'Landlord#2026'])->save();
        $this->otherLandlord = Landlord::factory()->create(['company_id' => $this->company->id]);

        // Juma's property: one rented unit, one free.
        $this->property = Property::factory()->create(['company_id' => $this->company->id, 'landlord_id' => $this->landlord->id, 'name' => 'Masaki Heights']);
        $building = Building::factory()->create(['company_id' => $this->company->id, 'property_id' => $this->property->id]);
        $rented = Unit::factory()->create(['company_id' => $this->company->id, 'building_id' => $building->id, 'unit_number' => 'A1', 'status' => 'occupied', 'rent_amount' => 500000]);
        Unit::factory()->create(['company_id' => $this->company->id, 'building_id' => $building->id, 'unit_number' => 'A2', 'status' => 'vacant', 'rent_amount' => 450000]);

        $this->tenant = Tenant::factory()->create(['company_id' => $this->company->id, 'name' => 'Amina Rented']);
        $lease = Lease::factory()->create(['company_id' => $this->company->id, 'tenant_id' => $this->tenant->id, 'unit_id' => $rented->id, 'status' => 'active', 'rent_amount' => 500000]);

        // 500,000 rent received this year; 120,000 spent on the property.
        $invoice = Invoice::factory()->create(['company_id' => $this->company->id, 'lease_id' => $lease->id, 'total_amount' => 500000, 'amount_paid' => 500000, 'status' => 'paid']);
        InvoicePayment::create(['company_id' => $this->company->id, 'invoice_id' => $invoice->id, 'amount' => 500000, 'paid_on' => company_now()->startOfYear()->addDays(10)->toDateString(), 'method' => 'cash']);
        $account = Account::factory()->create(['company_id' => $this->company->id, 'name' => 'Repairs']);
        Expense::factory()->create(['company_id' => $this->company->id, 'property_id' => $this->property->id, 'account_id' => $account->id, 'amount' => 120000, 'expense_date' => company_now()->startOfYear()->addDays(20)->toDateString()]);
        // A voided expense never counts.
        Expense::factory()->create(['company_id' => $this->company->id, 'property_id' => $this->property->id, 'account_id' => $account->id, 'amount' => 999999, 'status' => 'voided', 'expense_date' => company_now()->startOfYear()->addDays(20)->toDateString()]);

        // Someone else's property and tenant - never visible to Juma.
        $otherProperty = Property::factory()->create(['company_id' => $this->company->id, 'landlord_id' => $this->otherLandlord->id, 'name' => 'Other Owner Plaza']);
        $otherBuilding = Building::factory()->create(['company_id' => $this->company->id, 'property_id' => $otherProperty->id]);
        $otherUnit = Unit::factory()->create(['company_id' => $this->company->id, 'building_id' => $otherBuilding->id, 'unit_number' => 'Z9', 'status' => 'occupied']);
        $stranger = Tenant::factory()->create(['company_id' => $this->company->id, 'name' => 'Stranger Tenant']);
        Lease::factory()->create(['company_id' => $this->company->id, 'tenant_id' => $stranger->id, 'unit_id' => $otherUnit->id, 'status' => 'active']);
    }

    protected function token(): string
    {
        return $this->postJson('/api/v1/portal/landlord/login', ['email' => 'juma.landlord', 'password' => 'Landlord#2026'])->assertOk()->json('token');
    }

    protected function restrict(array $permissions): void
    {
        $role = LandlordAccess::role($this->company->id);
        PermissionsTeam::runAs($this->company->id, fn () => $role->syncPermissions($permissions));
    }

    // --- Portal ---------------------------------------------------------

    public function test_landlord_adds_and_edits_their_own_property_on_the_portal(): void
    {
        $this->actingAs($this->landlord, 'landlord');

        $this->get(route('landlord-portal.properties.index'))->assertOk()
            ->assertSee('Masaki Heights')->assertDontSee('Other Owner Plaza')
            ->assertSee(route('landlord-portal.properties.create'), false);
        $this->get(route('landlord-portal.properties.create'))->assertOk()
            ->assertSee('name="state"', false)->assertDontSee('name="landlord_id"', false);

        $this->post(route('landlord-portal.properties.store'), ['name' => 'Mbezi Villas', 'type' => 'residential', 'state' => 'Dar es Salaam', 'city' => 'Kinondoni', 'area' => 'Mbezi Beach'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $new = Property::where('name', 'Mbezi Villas')->firstOrFail();
        $this->assertSame($this->landlord->id, $new->landlord_id);
        $this->assertSame($this->company->id, $new->company_id);

        $this->get(route('landlord-portal.properties.edit', $new))->assertOk();
        $this->put(route('landlord-portal.properties.update', $new), ['name' => 'Mbezi Villas II', 'type' => 'residential', 'status' => 'active'])->assertRedirect();
        $this->assertSame('Mbezi Villas II', $new->fresh()->name);

        // Not someone else's.
        $theirs = Property::where('name', 'Other Owner Plaza')->firstOrFail();
        $this->get(route('landlord-portal.properties.edit', $theirs))->assertForbidden();
        $this->put(route('landlord-portal.properties.update', $theirs), ['name' => 'Hijack', 'type' => 'residential'])->assertForbidden();
        $this->assertSame('Other Owner Plaza', $theirs->fresh()->name);
    }

    public function test_landlord_sees_rented_and_free_units(): void
    {
        $this->actingAs($this->landlord, 'landlord');

        $this->get(route('landlord-portal.properties.show', $this->property))->assertOk()->assertSee('Rented')->assertSee('Free');

        $this->get(route('landlord-portal.reports.units'))->assertOk()->assertSee('A1')->assertSee('A2')->assertSee('Amina Rented')->assertDontSee('Z9');
        $this->get(route('landlord-portal.reports.units', ['status' => 'vacant']))->assertOk()->assertSee('A2')->assertDontSee('Amina Rented');
    }

    public function test_landlord_sees_and_adds_tenants_on_the_portal(): void
    {
        $this->actingAs($this->landlord, 'landlord');

        $this->get(route('landlord-portal.tenants.index'))->assertOk()->assertSee('Amina Rented')->assertDontSee('Stranger Tenant');

        $this->post(route('landlord-portal.tenants.store'), ['name' => 'Baraka New', 'type' => 'individual', 'phone' => '0713111222'])
            ->assertSessionHasNoErrors()->assertRedirect(route('landlord-portal.tenants.index'));

        $tenant = Tenant::where('name', 'Baraka New')->firstOrFail();
        $this->assertSame($this->landlord->id, $tenant->landlord_id);
        $this->assertNotNull($tenant->username);
        $this->assertNotNull($tenant->password, 'portal/app access granted straight away');
        $this->get(route('landlord-portal.tenants.index'))->assertSee('Baraka New');

        // Another landlord doesn't see them.
        $this->actingAs($this->otherLandlord, 'landlord')->get(route('landlord-portal.tenants.index'))->assertDontSee('Baraka New');
    }

    public function test_profit_and_loss_counts_only_the_landlords_money(): void
    {
        $this->actingAs($this->landlord, 'landlord');

        $this->get(route('landlord-portal.reports.profit-loss'))->assertOk()
            ->assertSee('500,000.00')->assertSee('120,000.00')->assertSee('380,000.00')
            ->assertDontSee('999,999.00');
    }

    // --- Roles ------------------------------------------------------------

    public function test_the_landlord_role_is_listed_and_editable_but_not_deletable(): void
    {
        $admin = PermissionsTeam::runAs($this->company->id, function () {
            Role::findOrCreate('Admin', 'web')->syncPermissions(['roles.viewAny', 'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'users.create', 'users.viewAny']);
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('Admin');

            return $user;
        });

        $this->actingAs($admin)->get(route('roles.index'))->assertOk()->assertSee('Landlord')->assertSee('Landlord portal &amp; app', false);
        $role = LandlordAccess::role($this->company->id);

        $this->actingAs($admin)->get(route('roles.edit', $role))->assertOk()
            ->assertSee('value="properties.create"', false)->assertDontSee('value="invoices.create"', false);

        // Not offered to staff.
        $this->actingAs($admin)->get(route('users.create'))->assertOk()->assertDontSee('value="Landlord"', false);

        // Untick "add properties" and "reports": landlords are told, and lose them.
        $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => 'Renamed',
            'permissions' => ['properties.viewAny', 'properties.update', 'tenants.viewAny', 'tenants.create', 'invoices.create'],
        ])->assertRedirect();

        $role->refresh();
        $this->assertSame('Landlord', $role->name, 'name is fixed');
        $this->assertEqualsCanonicalizing(['properties.viewAny', 'properties.update', 'tenants.viewAny', 'tenants.create'], LandlordAccess::permissionsOf($this->landlord));
        $notice = $this->landlord->fresh()->notifications()->firstOrFail();
        $this->assertSame(AccessChanged::class, $notice->type);
        $this->assertContains('properties.create', $notice->data['revoked']);

        $this->actingAs($admin)->delete(route('roles.destroy', $role))->assertForbidden();
        $this->assertNotNull(Role::find($role->id));
    }

    public function test_restricting_the_role_hides_and_blocks_the_feature_everywhere(): void
    {
        $this->restrict(['properties.viewAny']);

        $this->actingAs($this->landlord, 'landlord');
        $this->get(route('landlord-portal.properties.index'))->assertOk()->assertDontSee(route('landlord-portal.properties.create'), false);
        $this->get(route('landlord-portal.dashboard'))->assertOk()->assertDontSee(route('landlord-portal.tenants.index'), false)->assertDontSee(route('landlord-portal.reports.profit-loss'), false);
        $this->get(route('landlord-portal.properties.create'))->assertForbidden();
        $this->post(route('landlord-portal.properties.store'), ['name' => 'Sneaky', 'type' => 'residential'])->assertForbidden();
        $this->get(route('landlord-portal.tenants.index'))->assertForbidden();
        $this->get(route('landlord-portal.reports.profit-loss'))->assertForbidden();
        $this->get(route('landlord-portal.expenses.index'))->assertForbidden();

        $token = $this->token();
        $this->withToken($token)->getJson('/api/v1/portal/landlord/properties')->assertOk();
        $this->withToken($token)->postJson('/api/v1/portal/landlord/properties', ['name' => 'Sneaky', 'type' => 'residential'])->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/portal/landlord/tenants')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/portal/landlord/reports/profit-loss')->assertForbidden();
        $this->assertDatabaseMissing('properties', ['name' => 'Sneaky']);
    }

    // --- App API ----------------------------------------------------------

    public function test_the_app_api_covers_properties_tenants_and_reports(): void
    {
        $login = $this->postJson('/api/v1/portal/landlord/login', ['email' => 'juma.landlord', 'password' => 'Landlord#2026'])->assertOk();
        $this->assertEqualsCanonicalizing(LandlordAccess::names(), $login->json('permissions'));
        $token = $login->json('token');
        $this->assertEqualsCanonicalizing(LandlordAccess::names(), $this->withToken($token)->getJson('/api/v1/portal/landlord/profile')->json('permissions'));

        // Properties: list with rented/free counts, add, edit.
        $list = $this->withToken($token)->getJson('/api/v1/portal/landlord/properties')->assertOk();
        $this->assertSame(['Masaki Heights'], collect($list->json('data'))->pluck('name')->all());
        $this->assertSame(1, $list->json('data.0.occupied_units_count'));
        $this->assertSame(1, $list->json('data.0.vacant_units_count'));
        $this->withToken($token)->getJson('/api/v1/portal/landlord/property-options')->assertOk()->assertJsonPath('types.0', 'residential');

        $created = $this->withToken($token)->postJson('/api/v1/portal/landlord/properties', ['name' => 'App Towers', 'type' => 'commercial', 'state' => 'Arusha'])->assertCreated();
        $uuid = $created->json('data.uuid');
        $this->withToken($token)->putJson("/api/v1/portal/landlord/properties/{$uuid}", ['name' => 'App Towers 2', 'type' => 'commercial', 'status' => 'active'])
            ->assertOk()->assertJsonPath('data.name', 'App Towers 2');
        $this->withToken($token)->postJson('/api/v1/portal/landlord/properties', ['type' => 'nope'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'type']);

        // Tenants: list, add, detail; someone else's tenant is not found.
        $tenants = $this->withToken($token)->getJson('/api/v1/portal/landlord/tenants')->assertOk();
        $this->assertSame(['Amina Rented'], collect($tenants->json('data'))->pluck('name')->all());
        $this->assertSame('A1', $tenants->json('data.0.current_lease.unit_number'));

        $added = $this->withToken($token)->postJson('/api/v1/portal/landlord/tenants', ['name' => 'Neema App', 'type' => 'individual', 'phone' => '0715000999'])->assertCreated();
        $this->assertNotEmpty($added->json('login.username'));
        $this->assertNotEmpty($added->json('login.password'));
        $this->withToken($token)->getJson('/api/v1/portal/landlord/tenants/'.$added->json('data.uuid'))->assertOk()->assertJsonPath('data.name', 'Neema App');
        $this->withToken($token)->getJson('/api/v1/portal/landlord/tenants?status=no_lease')->assertOk()->assertJsonPath('data.0.name', 'Neema App');
        $stranger = Tenant::where('name', 'Stranger Tenant')->firstOrFail();
        $this->withToken($token)->getJson("/api/v1/portal/landlord/tenants/{$stranger->uuid}")->assertNotFound();

        // Reports.
        $pl = $this->withToken($token)->getJson('/api/v1/portal/landlord/reports/profit-loss')->assertOk();
        $this->assertEquals(500000, $pl->json('data.income.total'));
        $this->assertEquals(120000, $pl->json('data.expenses.total'));
        $this->assertEquals(380000, $pl->json('data.net'));
        $this->assertSame('Repairs', $pl->json('data.expenses.by_category.0.category'));

        $units = $this->withToken($token)->getJson('/api/v1/portal/landlord/reports/units')->assertOk();
        $this->assertSame(['total' => 2, 'occupied' => 1, 'vacant' => 1], collect($units->json('data.summary'))->only(['total', 'occupied', 'vacant'])->all());
        $free = $this->withToken($token)->getJson('/api/v1/portal/landlord/reports/units?status=vacant')->assertOk();
        $this->assertSame(['A2'], collect($free->json('data.units'))->pluck('unit_number')->all());
    }
}
