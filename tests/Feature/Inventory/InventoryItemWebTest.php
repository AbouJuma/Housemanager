<?php

namespace Tests\Feature\Inventory;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryItemWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['inventory.viewAny', 'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_saas_owner_cannot_access_inventory(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('inventory-items.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_add_an_inventory_item(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('inventory-items.store'), [
            'name' => 'LED Bulb',
            'category' => 'electrical_supply',
            'unit_of_measure' => 'piece',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['company_id' => $company->id, 'name' => 'LED Bulb', 'quantity_on_hand' => 0]);
    }

    public function test_a_company_admin_cannot_view_another_companys_item(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherItem = InventoryItem::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('inventory-items.show', $otherItem))->assertForbidden();
    }

    public function test_recording_a_receipt_transaction_increases_quantity_on_hand(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 5]);

        $response = $this->actingAs($admin)->post(route('inventory-items.record-transaction', $item), [
            'type' => 'receipt',
            'quantity' => 10,
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertSame('15.00', (string) $item->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_transactions', ['inventory_item_id' => $item->id, 'type' => 'receipt', 'quantity' => 10]);
    }

    public function test_recording_a_usage_transaction_beyond_stock_on_hand_is_rejected(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 2]);

        $response = $this->actingAs($admin)->post(route('inventory-items.record-transaction', $item), [
            'type' => 'usage',
            'quantity' => 5,
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame('2.00', (string) $item->fresh()->quantity_on_hand);
    }

    public function test_deleting_an_inventory_item_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $item = InventoryItem::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->delete(route('inventory-items.destroy', $item));

        $response->assertRedirect(route('inventory-items.index'));
        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
    }
}
