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

class UnitPolicyTest extends TestCase
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

    public function test_a_company_user_with_permissions_can_manage_units_in_their_own_building(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['properties.view', 'properties.create', 'properties.update', 'properties.delete']);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $building, $unit): void {
            $this->assertTrue($user->can('create', [Unit::class, $building]));
            $this->assertTrue($user->can('view', $unit));
            $this->assertTrue($user->can('update', $unit));
            $this->assertTrue($user->can('delete', $unit));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['properties.view', 'properties.create', 'properties.update', 'properties.delete']);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherBuilding = Building::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);
        $otherUnit = Unit::factory()->create(['company_id' => $otherCompany->id, 'building_id' => $otherBuilding->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherBuilding, $otherUnit): void {
            $this->assertFalse($user->can('create', [Unit::class, $otherBuilding]));
            $this->assertFalse($user->can('view', $otherUnit));
            $this->assertFalse($user->can('update', $otherUnit));
            $this->assertFalse($user->can('delete', $otherUnit));
        });
    }
}
