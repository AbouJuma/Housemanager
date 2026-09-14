<?php

namespace Tests\Feature\Vendors;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Vendor Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_vendors(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($owner->can('viewAny', Vendor::class));
        $this->assertFalse($owner->can('view', $vendor));
        $this->assertFalse($owner->can('create', Vendor::class));
    }

    public function test_a_company_user_without_permissions_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($user->can('viewAny', Vendor::class));
        $this->assertFalse($user->can('view', $vendor));
        $this->assertFalse($user->can('update', $vendor));
        $this->assertFalse($user->can('delete', $vendor));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_vendors(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['vendors.viewAny', 'vendors.view', 'vendors.create', 'vendors.update', 'vendors.delete']);
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $vendor): void {
            $this->assertTrue($user->can('viewAny', Vendor::class));
            $this->assertTrue($user->can('view', $vendor));
            $this->assertTrue($user->can('create', Vendor::class));
            $this->assertTrue($user->can('update', $vendor));
            $this->assertTrue($user->can('delete', $vendor));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['vendors.view', 'vendors.update', 'vendors.delete']);
        $otherVendor = Vendor::factory()->create(['company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherVendor): void {
            $this->assertFalse($user->can('view', $otherVendor));
            $this->assertFalse($user->can('update', $otherVendor));
            $this->assertFalse($user->can('delete', $otherVendor));
        });
    }
}
