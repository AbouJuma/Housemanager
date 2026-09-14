<?php

namespace Tests\Feature\Properties;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyApiTest extends TestCase
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

    public function test_a_company_admin_can_list_their_own_properties_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        Property::factory()->count(2)->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/properties')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_company_admin_can_create_a_property_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/properties', ['name' => 'Sunset Apartments', 'type' => 'residential']);

        $response->assertCreated()->assertJsonPath('data.name', 'Sunset Apartments');
        $this->assertDatabaseHas('properties', ['name' => 'Sunset Apartments', 'company_id' => $company->id]);
    }

    public function test_a_company_admin_cannot_view_another_companys_property_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);

        $this->getJson("/api/v1/properties/{$otherProperty->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_property_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $this->deleteJson("/api/v1/properties/{$property->uuid}")->assertNoContent();

        $this->assertSoftDeleted('properties', ['id' => $property->id]);
    }
}
