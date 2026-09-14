<?php

namespace Tests\Feature\Leases;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeasePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUnit(Company $company): Unit
    {
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        return Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id]);
    }

    protected function makeLease(Company $company): Lease
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company);

        return Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
    }

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Lease Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_leases(): void
    {
        $owner = User::factory()->create();
        $lease = $this->makeLease(Company::factory()->create());

        $this->assertFalse($owner->can('viewAny', Lease::class));
        $this->assertFalse($owner->can('view', $lease));
        $this->assertFalse($owner->can('create', Lease::class));
    }

    public function test_a_company_user_without_permissions_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $lease = $this->makeLease($company);

        $this->assertFalse($user->can('viewAny', Lease::class));
        $this->assertFalse($user->can('view', $lease));
        $this->assertFalse($user->can('update', $lease));
        $this->assertFalse($user->can('delete', $lease));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_leases(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['leases.viewAny', 'leases.view', 'leases.create', 'leases.update', 'leases.delete']);
        $lease = $this->makeLease($company);

        PermissionsTeam::runAs($company->id, function () use ($user, $lease): void {
            $this->assertTrue($user->can('viewAny', Lease::class));
            $this->assertTrue($user->can('view', $lease));
            $this->assertTrue($user->can('create', Lease::class));
            $this->assertTrue($user->can('update', $lease));
            $this->assertTrue($user->can('delete', $lease));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['leases.view', 'leases.update', 'leases.delete']);
        $otherLease = $this->makeLease($otherCompany);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherLease): void {
            $this->assertFalse($user->can('view', $otherLease));
            $this->assertFalse($user->can('update', $otherLease));
            $this->assertFalse($user->can('delete', $otherLease));
        });
    }
}
