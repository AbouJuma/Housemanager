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
}
