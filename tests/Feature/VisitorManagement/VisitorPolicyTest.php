<?php

namespace Tests\Feature\VisitorManagement;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitorPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeVisitor(Company $company): Visitor
    {
        $property = Property::factory()->create(['company_id' => $company->id]);

        return Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
    }

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Front Desk', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_visitors(): void
    {
        $owner = User::factory()->create();
        $visitor = $this->makeVisitor(Company::factory()->create());

        $this->assertFalse($owner->can('viewAny', Visitor::class));
        $this->assertFalse($owner->can('view', $visitor));
        $this->assertFalse($owner->can('create', Visitor::class));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_visitors(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['visitors.viewAny', 'visitors.view', 'visitors.create', 'visitors.update', 'visitors.delete']);
        $visitor = $this->makeVisitor($company);

        PermissionsTeam::runAs($company->id, function () use ($user, $visitor): void {
            $this->assertTrue($user->can('viewAny', Visitor::class));
            $this->assertTrue($user->can('view', $visitor));
            $this->assertTrue($user->can('create', Visitor::class));
            $this->assertTrue($user->can('update', $visitor));
            $this->assertTrue($user->can('delete', $visitor));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['visitors.view', 'visitors.update', 'visitors.delete']);
        $otherVisitor = $this->makeVisitor($otherCompany);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherVisitor): void {
            $this->assertFalse($user->can('view', $otherVisitor));
            $this->assertFalse($user->can('update', $otherVisitor));
            $this->assertFalse($user->can('delete', $otherVisitor));
        });
    }
}
