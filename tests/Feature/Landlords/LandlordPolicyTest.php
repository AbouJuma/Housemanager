<?php

namespace Tests\Feature\Landlords;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Landlord Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_landlords(): void
    {
        $owner = User::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($owner->can('viewAny', Landlord::class));
        $this->assertFalse($owner->can('view', $landlord));
        $this->assertFalse($owner->can('create', Landlord::class));
    }

    public function test_a_company_user_without_permissions_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($user->can('viewAny', Landlord::class));
        $this->assertFalse($user->can('view', $landlord));
        $this->assertFalse($user->can('update', $landlord));
        $this->assertFalse($user->can('delete', $landlord));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_landlords(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['landlords.viewAny', 'landlords.view', 'landlords.create', 'landlords.update', 'landlords.delete']);
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $landlord): void {
            $this->assertTrue($user->can('viewAny', Landlord::class));
            $this->assertTrue($user->can('view', $landlord));
            $this->assertTrue($user->can('create', Landlord::class));
            $this->assertTrue($user->can('update', $landlord));
            $this->assertTrue($user->can('delete', $landlord));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['landlords.view', 'landlords.update', 'landlords.delete']);
        $otherLandlord = Landlord::factory()->create(['company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherLandlord): void {
            $this->assertFalse($user->can('view', $otherLandlord));
            $this->assertFalse($user->can('update', $otherLandlord));
            $this->assertFalse($user->can('delete', $otherLandlord));
        });
    }
}
