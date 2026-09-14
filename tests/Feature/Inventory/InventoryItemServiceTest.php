<?php

namespace Tests\Feature\Inventory;

use App\Modules\Companies\Models\Company;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Repositories\EloquentInventoryItemRepository;
use App\Modules\Inventory\Repositories\InventoryItemRepositoryInterface;
use App\Modules\Inventory\Services\InventoryItemService;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryItemServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryItemService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InventoryItemService::class);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentInventoryItemRepository::class, app(InventoryItemRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_category_status_and_low_stock(): void
    {
        $company = Company::factory()->create();
        InventoryItem::factory()->create(['company_id' => $company->id, 'name' => 'LED Bulb', 'category' => 'electrical_supply', 'quantity_on_hand' => 20, 'reorder_level' => 5]);
        InventoryItem::factory()->create(['company_id' => $company->id, 'name' => 'Padlock', 'category' => 'hardware', 'status' => 'discontinued', 'quantity_on_hand' => 1, 'reorder_level' => 5]);

        $this->assertSame(1, $this->service->paginate('LED', null, null, false)->total());
        $this->assertSame(1, $this->service->paginate(null, 'hardware', null, false)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'discontinued', false)->total());
        $this->assertSame(1, $this->service->paginate(null, null, null, true)->total());
        $this->assertSame(2, $this->service->paginate(null, null, null, false)->total());
    }

    public function test_create_always_starts_quantity_on_hand_at_zero_even_if_supplied(): void
    {
        $company = Company::factory()->create();

        $item = $this->service->create([
            'company_id' => $company->id,
            'name' => 'Test Item',
            'category' => 'other',
            'unit_of_measure' => 'piece',
            'quantity_on_hand' => 999,
        ]);

        $this->assertSame('0.00', (string) $item->quantity_on_hand);
    }

    public function test_update_never_lets_quantity_on_hand_be_set_directly(): void
    {
        $company = Company::factory()->create();
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 10]);

        $updated = $this->service->update($item, [
            'name' => $item->name,
            'category' => $item->category,
            'unit_of_measure' => $item->unit_of_measure,
            'status' => 'active',
            'quantity_on_hand' => 500,
        ]);

        $this->assertSame('10.00', (string) $updated->quantity_on_hand);
    }

    public function test_record_transaction_receipt_increases_quantity_and_logs_the_snapshot(): void
    {
        $company = Company::factory()->create();
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 5]);

        $updated = $this->service->recordTransaction($item, [
            'type' => 'receipt',
            'quantity' => 10,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertSame('15.00', (string) $updated->quantity_on_hand);
        $transaction = $updated->transactions()->first();
        $this->assertSame('10.00', (string) $transaction->quantity);
        $this->assertSame('5.00', (string) $transaction->quantity_before);
        $this->assertSame('15.00', (string) $transaction->quantity_after);
    }

    public function test_record_transaction_usage_decreases_quantity(): void
    {
        $company = Company::factory()->create();
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 10]);

        $updated = $this->service->recordTransaction($item, [
            'type' => 'usage',
            'quantity' => 4,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertSame('6.00', (string) $updated->quantity_on_hand);
        $this->assertSame('-4.00', (string) $updated->transactions()->first()->quantity);
    }

    public function test_record_transaction_usage_rejects_taking_stock_below_zero(): void
    {
        $company = Company::factory()->create();
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 2]);

        $this->expectException(ValidationException::class);

        $this->service->recordTransaction($item, [
            'type' => 'usage',
            'quantity' => 5,
            'transaction_date' => now()->toDateString(),
        ]);
    }

    public function test_record_transaction_adjustment_respects_the_chosen_direction(): void
    {
        $company = Company::factory()->create();
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 10]);

        $increased = $this->service->recordTransaction($item, [
            'type' => 'adjustment',
            'quantity' => 3,
            'direction' => 'increase',
            'reason' => 'Found extra stock',
            'transaction_date' => now()->toDateString(),
        ]);
        $this->assertSame('13.00', (string) $increased->quantity_on_hand);

        $decreased = $this->service->recordTransaction($increased, [
            'type' => 'adjustment',
            'quantity' => 5,
            'direction' => 'decrease',
            'reason' => 'Damaged stock written off',
            'transaction_date' => now()->toDateString(),
        ]);
        $this->assertSame('8.00', (string) $decreased->quantity_on_hand);
    }

    public function test_record_transaction_usage_can_link_back_to_a_maintenance_request(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $request = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $item = InventoryItem::factory()->create(['company_id' => $company->id, 'quantity_on_hand' => 10]);

        $updated = $this->service->recordTransaction($item, [
            'type' => 'usage',
            'quantity' => 2,
            'maintenance_request_id' => $request->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertSame($request->id, $updated->transactions()->first()->maintenance_request_id);
        $this->assertCount(1, $request->fresh()->inventoryTransactions);
    }
}
