<?php

namespace App\Modules\Inventory\Database\Factories;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 *
 * For model-level tests only - a factory-made transaction doesn't move
 * its item's quantity_on_hand the way a real one does. Seeded, realistic
 * transaction history goes through InventoryItemService::
 * recordTransaction() instead (see InventoryItemSeeder), the same
 * "seeders shouldn't bypass real business logic" discipline as
 * ExpenseSeeder/MaintenanceRequestSeeder.
 */
class InventoryTransactionFactory extends Factory
{
    protected $model = InventoryTransaction::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 10);

        return [
            'inventory_item_id' => InventoryItem::factory(),
            'type' => 'receipt',
            'quantity' => $quantity,
            'quantity_before' => 0,
            'quantity_after' => $quantity,
            'transaction_date' => now()->subDays(random_int(0, 30))->toDateString(),
        ];
    }
}
