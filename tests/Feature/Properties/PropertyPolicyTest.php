<?php

namespace Tests\Feature\Properties;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyPolicyTest extends TestCase
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

    public function test_saas_owner_cannot_manage_properties(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($owner->can('viewAny', Property::class));
        $this->assertFalse($owner->can('view', $property));
        $this->assertFalse($owner->can('create', Property::class));
    }

    public function test_a_company_user_without_permissions_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($user->can('viewAny', Property::class));
        $this->assertFalse($user->can('view', $property));
        $this->assertFalse($user->can('create', Property::class));
        $this->assertFalse($user->can('update', $property));
        $this->assertFalse($user->can('delete', $property));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_properties(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['properties.viewAny', 'properties.view', 'properties.create', 'properties.update', 'properties.delete']);
        $property = Property::factory()->create(['company_id' => $company->id]);

        // hasPermissionTo() reads the *current* team context, which
        // isn't the acting user's own company by default outside a real
        // request (IdentifyCompany sets it there) - see the teams
        // caveat in ARCHITECTURE.md and RolePolicyTest's identical
        // pattern.
        PermissionsTeam::runAs($company->id, function () use ($user, $property): void {
            $this->assertTrue($user->can('viewAny', Property::class));
            $this->assertTrue($user->can('view', $property));
            $this->assertTrue($user->can('create', Property::class));
            $this->assertTrue($user->can('update', $property));
            $this->assertTrue($user->can('delete', $property));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['properties.view', 'properties.update', 'properties.delete']);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherProperty): void {
            $this->assertFalse($user->can('view', $otherProperty));
            $this->assertFalse($user->can('update', $otherProperty));
            $this->assertFalse($user->can('delete', $otherProperty));
        });
    }
}
