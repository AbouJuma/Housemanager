<?php

namespace App\Modules\Inventory\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Services\InventoryItemService;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Seeder;

/**
 * Goes through InventoryItemService::recordTransaction() for every
 * seeded stock movement (never a raw InventoryTransaction::create()) -
 * the same "seeders shouldn't bypass real business logic" discipline as
 * ExpenseSeeder/MaintenanceRequestSeeder, since quantity_on_hand must
 * stay consistent with its transaction history.
 */
class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(InventoryItemService::class);

        Company::query()->each(function (Company $company) use ($service): void {
            $properties = Property::query()->where('company_id', $company->id)->get();
            $vendors = Vendor::query()->where('company_id', $company->id)->where('status', 'active')->get();
            $openMaintenanceRequests = MaintenanceRequest::query()
                ->where('company_id', $company->id)
                ->whereIn('status', ['open', 'in_progress', 'completed'])
                ->get();

            for ($i = 0; $i < random_int(5, 10); $i++) {
                $item = InventoryItem::factory()->create([
                    'company_id' => $company->id,
                    'property_id' => $properties->isNotEmpty() && fake()->boolean(50) ? $properties->random()->id : null,
                    'vendor_id' => $vendors->isNotEmpty() && fake()->boolean(50) ? $vendors->random()->id : null,
                ]);

                // Every item's history starts with a real receipt, since
                // quantity_on_hand always starts at zero - see
                // InventoryItemService::create().
                $service->recordTransaction($item, [
                    'type' => 'receipt',
                    'quantity' => fake()->randomFloat(2, 10, 50),
                    'transaction_date' => now()->subDays(random_int(20, 40))->toDateString(),
                ]);

                if (fake()->boolean(60)) {
                    $usageAttributes = [
                        'type' => 'usage',
                        'quantity' => fake()->randomFloat(2, 1, 5),
                        'transaction_date' => now()->subDays(random_int(1, 15))->toDateString(),
                    ];

                    if ($openMaintenanceRequests->isNotEmpty() && fake()->boolean(40)) {
                        $usageAttributes['maintenance_request_id'] = $openMaintenanceRequests->random()->id;
                    }

                    $service->recordTransaction($item, $usageAttributes);
                }

                if (fake()->boolean(20)) {
                    $service->recordTransaction($item, [
                        'type' => 'adjustment',
                        'quantity' => fake()->randomFloat(2, 1, 3),
                        'direction' => 'decrease',
                        'reason' => 'Stock count correction',
                        'transaction_date' => now()->subDays(random_int(1, 10))->toDateString(),
                    ]);
                }
            }
        });
    }
}
