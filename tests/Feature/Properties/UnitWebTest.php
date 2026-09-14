<?php

namespace Tests\Feature\Properties;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnitWebTest extends TestCase
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

    public function test_a_company_admin_can_create_a_unit_under_their_own_building(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $response = $this->actingAs($admin)->post(route('units.store', $building), [
            'unit_number' => 'A101',
            'type' => 'apartment',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('units', ['unit_number' => 'A101', 'building_id' => $building->id, 'company_id' => $company->id]);
    }

    public function test_a_company_admin_cannot_view_the_create_form_for_another_companys_building(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);

        $this->actingAs($admin)->get(route('units.create', $otherBuilding))->assertForbidden();
    }

    public function test_a_company_admin_cannot_view_another_companys_unit(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);
        $otherUnit = Unit::factory()->create(['company_id' => $otherCompany->id, 'building_id' => $otherBuilding->id]);

        $this->actingAs($admin)->get(route('units.show', $otherUnit))->assertForbidden();
    }

    public function test_updating_a_unit_number_must_stay_unique_within_the_building(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => 'A101']);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => 'A102']);

        $response = $this->actingAs($admin)->put(route('units.update', $unit), [
            'unit_number' => 'A101',
            'type' => 'apartment',
            'status' => 'vacant',
        ]);

        $response->assertSessionHasErrors('unit_number');
    }

    public function test_deleting_a_unit_redirects_to_its_building(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id]);

        $response = $this->actingAs($admin)->delete(route('units.destroy', $unit));

        $response->assertRedirect(route('buildings.show', $building));
        $this->assertSoftDeleted('units', ['id' => $unit->id]);
    }
}
