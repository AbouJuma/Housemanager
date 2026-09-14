<?php

namespace Tests\Feature\Properties;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuildingWebTest extends TestCase
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

    public function test_a_company_admin_can_create_a_building_under_their_own_property(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->post(route('buildings.store', $property), [
            'name' => 'Block A',
            'floor_count' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('buildings', ['name' => 'Block A', 'property_id' => $property->id, 'company_id' => $company->id]);
    }

    public function test_a_company_admin_cannot_view_the_create_form_for_another_companys_property(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('buildings.create', $otherProperty))->assertForbidden();
    }

    public function test_a_company_admin_cannot_store_a_building_under_another_companys_property(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->post(route('buildings.store', $otherProperty), ['name' => 'Block A'])->assertForbidden();
    }

    public function test_a_company_admin_cannot_view_another_companys_building(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);

        $this->actingAs($admin)->get(route('buildings.show', $otherBuilding))->assertForbidden();
    }

    public function test_deleting_a_building_redirects_to_its_property(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $response = $this->actingAs($admin)->delete(route('buildings.destroy', $building));

        $response->assertRedirect(route('properties.show', $property));
        $this->assertSoftDeleted('buildings', ['id' => $building->id]);
    }
}
