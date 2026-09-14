<?php

namespace Tests\Feature\Inventory;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryItemPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Inventory Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_inventory(): void
    {
        $owner = User::factory()->create();
        $item = InventoryItem::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($owner->can('viewAny', InventoryItem::class));
        $this->assertFalse($owner->can('view', $item));
        $this->assertFalse($owner->can('create', InventoryItem::class));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['inventory.viewAny', 'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete']);
        $item = InventoryItem::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $item): void {
            $this->assertTrue($user->can('viewAny', InventoryItem::class));
            $this->assertTrue($user->can('view', $item));
            $this->assertTrue($user->can('create', InventoryItem::class));
            $this->assertTrue($user->can('update', $item));
            $this->assertTrue($user->can('delete', $item));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['inventory.view', 'inventory.update', 'inventory.delete']);
        $otherItem = InventoryItem::factory()->create(['company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherItem): void {
            $this->assertFalse($user->can('view', $otherItem));
            $this->assertFalse($user->can('update', $otherItem));
            $this->assertFalse($user->can('delete', $otherItem));
        });
    }
}
