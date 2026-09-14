<?php

namespace Tests\Feature\Inventory;

use App\Modules\Companies\Models\Company;
use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryItemModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $company = Company::factory()->create();
        $item = InventoryItem::factory()->create(['company_id' => $company->id]);

        $this->assertTrue(Str::isUuid($item->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new InventoryItem())->getRouteKeyName());
    }

    public function test_is_low_stock_reflects_quantity_versus_reorder_level(): void
    {
        $company = Company::factory()->create();
        $lowStock = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 2, 'reorder_level' => 5]);
        $wellStocked = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 20, 'reorder_level' => 5]);
        $noThreshold = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 0, 'reorder_level' => null]);

        $this->assertTrue($lowStock->isLowStock());
        $this->assertFalse($wellStocked->isLowStock());
        $this->assertFalse($noThreshold->isLowStock());
    }

    public function test_new_items_start_with_zero_quantity_on_hand_by_default(): void
    {
        $company = Company::factory()->create();
        $item = InventoryItem::factory()->create(['company_id' => $company->id]);

        $this->assertSame('0.00', (string) $item->quantity_on_hand);
    }
}
