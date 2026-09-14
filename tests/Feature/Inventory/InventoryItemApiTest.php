<?php

namespace Tests\Feature\Inventory;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryItemApiTest extends TestCase
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

    public function test_a_company_admin_can_list_their_own_items_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        InventoryItem::factory()->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/inventory-items')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_company_admin_can_create_an_item_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/inventory-items', [
            'name' => 'LED Bulb',
            'category' => 'electrical_supply',
            'unit_of_measure' => 'piece',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'LED Bulb')->assertJsonPath('data.quantity_on_hand', 0);
    }

    public function test_a_company_admin_cannot_view_another_companys_item_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherItem = InventoryItem::factory()->create(['company_id' => $otherCompany->id]);

        $this->getJson("/api/v1/inventory-items/{$otherItem->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_record_a_transaction_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 5]);

        $response = $this->postJson("/api/v1/inventory-items/{$item->uuid}/record-transaction", [
            'type' => 'receipt',
            'quantity' => 10,
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertOk();
        $this->assertSame(15.0, (float) $response->json('data.quantity_on_hand'));
    }

    public function test_a_company_admin_can_delete_their_own_item_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $item = InventoryItem::factory()->create(['company_id' => $company->id]);

        $this->deleteJson("/api/v1/inventory-items/{$item->uuid}")->assertNoContent();

        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
    }
}
