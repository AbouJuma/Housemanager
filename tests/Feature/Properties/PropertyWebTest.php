<?php

namespace Tests\Feature\Properties;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['properties.viewAny', 'properties.view', 'properties.create', 'properties.update', 'properties.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_saas_owner_cannot_access_properties(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('properties.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_list_their_own_properties(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Property::factory()->count(2)->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->get(route('properties.index'));

        $response->assertOk();
        $response->assertViewHas('properties', fn ($properties) => $properties->total() === 2);
    }

    /**
     * The "Add New" landlord button/modal on this form only appears for a
     * user who can actually create a landlord (@can('create', Landlord::class)
     * in properties::properties._form) - this admin has properties.* only.
     */
    public function test_the_create_form_hides_the_add_landlord_button_without_landlord_permission(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('properties.create'))
            ->assertOk()
            ->assertDontSee('id="landlordModal"', false);
    }

    public function test_the_create_form_shows_the_add_landlord_button_with_landlord_permission(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        PermissionsTeam::runAs($company->id, fn () => $admin->givePermissionTo('landlords.create'));

        $this->actingAs($admin)->get(route('properties.create'))
            ->assertOk()
            ->assertSee('id="landlordModal"', false)
            ->assertSee(route('landlords.store'));
    }

    public function test_a_company_admin_can_create_a_property(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('properties.store'), [
            'name' => 'Sunset Apartments',
            'type' => 'residential',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', ['name' => 'Sunset Apartments', 'company_id' => $company->id]);
    }

    public function test_a_company_admin_can_assign_one_of_their_own_landlords_to_a_property(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->post(route('properties.store'), [
            'name' => 'Sunset Apartments',
            'type' => 'residential',
            'landlord_id' => $landlord->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', ['name' => 'Sunset Apartments', 'landlord_id' => $landlord->id]);
    }

    public function test_a_company_admin_cannot_view_another_companys_property(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('properties.show', $otherProperty))->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_property(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->delete(route('properties.destroy', $property));

        $response->assertRedirect(route('properties.index'));
        $this->assertSoftDeleted('properties', ['id' => $property->id]);
    }
    public function test_the_add_landlord_modal_is_rendered_outside_the_property_form(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        PermissionsTeam::runAs($company->id, fn () => Role::findByName("Admin", "web")->givePermissionTo("landlords.create"));
        $admin->unsetRelation("roles")->unsetRelation("permissions");

        $html = $this->actingAs($admin)->get(route("properties.create"))->assertOk()->getContent();

        // The modal has its own `required` inputs - nested inside the
        // property form they silently blocked every submit.
        $formEnd = strpos($html, "</form>", strpos($html, route("properties.store")));
        $this->assertNotFalse(strpos($html, "id=\"landlordModal\""));
        $this->assertGreaterThan($formEnd, strpos($html, "id=\"landlordModal\""));
    }
    public function test_a_property_saves_its_tanzanian_region_district_and_area(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->post(route('properties.store'), [
            'name' => 'Masaki Heights',
            'type' => 'residential',
            'state' => 'Dar es Salaam',
            'city' => 'Kinondoni',
            'area' => 'Masaki',
            'country_code' => 'TZ',
        ])->assertRedirect();

        $this->assertDatabaseHas('properties', ['name' => 'Masaki Heights', 'state' => 'Dar es Salaam', 'city' => 'Kinondoni', 'area' => 'Masaki']);
    }

    public function test_the_property_list_filters_by_region_and_district(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Property::factory()->create(['company_id' => $company->id, 'name' => 'Masaki Heights', 'state' => 'Dar es Salaam', 'city' => 'Kinondoni']);
        Property::factory()->create(['company_id' => $company->id, 'name' => 'Kariakoo Plaza', 'state' => 'Dar es Salaam', 'city' => 'Ilala']);
        Property::factory()->create(['company_id' => $company->id, 'name' => 'Njiro Villas', 'state' => 'Arusha', 'city' => 'Arusha City']);

        $this->actingAs($admin)->get(route('properties.index', ['region' => 'Dar es Salaam']))
            ->assertViewHas('properties', fn ($properties) => $properties->total() === 2);

        $this->actingAs($admin)->get(route('properties.index', ['region' => 'Dar es Salaam', 'district' => 'Ilala']))
            ->assertOk()
            ->assertViewHas('properties', fn ($properties) => $properties->pluck('name')->all() === ['Kariakoo Plaza']);
    }
}