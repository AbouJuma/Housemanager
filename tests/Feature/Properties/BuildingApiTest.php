<?php

namespace Tests\Feature\Properties;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuildingApiTest extends TestCase
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

    public function test_a_company_admin_can_list_buildings_for_their_own_property_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);
        Building::factory()->count(2)->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->getJson("/api/v1/properties/{$property->uuid}/buildings")->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_company_admin_can_create_a_building_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $response = $this->postJson("/api/v1/properties/{$property->uuid}/buildings", ['name' => 'Block A']);

        $response->assertCreated()->assertJsonPath('data.name', 'Block A');
    }

    public function test_a_company_admin_cannot_view_another_companys_building_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);

        $this->getJson("/api/v1/buildings/{$otherBuilding->uuid}")->assertForbidden();
    }
}
