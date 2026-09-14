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

class BuildingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Property Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_a_company_user_with_permissions_can_manage_buildings_in_their_own_property(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['properties.view', 'properties.create', 'properties.update', 'properties.delete']);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $property, $building): void {
            $this->assertTrue($user->can('create', [Building::class, $property]));
            $this->assertTrue($user->can('view', $building));
            $this->assertTrue($user->can('update', $building));
            $this->assertTrue($user->can('delete', $building));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['properties.view', 'properties.create', 'properties.update', 'properties.delete']);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherProperty, $otherBuilding): void {
            $this->assertFalse($user->can('create', [Building::class, $otherProperty]));
            $this->assertFalse($user->can('view', $otherBuilding));
            $this->assertFalse($user->can('update', $otherBuilding));
            $this->assertFalse($user->can('delete', $otherBuilding));
        });
    }
}
