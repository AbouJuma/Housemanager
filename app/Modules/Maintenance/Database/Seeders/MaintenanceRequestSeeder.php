<?php

namespace App\Modules\Maintenance\Database\Seeders;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Maintenance\Services\MaintenanceRequestService;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Seeder;

/**
 * Base records are created directly (MaintenanceRequestService::create()
 * has no ledger-posting logic to bypass, unlike InvoiceSeeder/
 * ExpenseSeeder), but recordExpense() - real business logic that posts
 * to the Accounting ledger - is used for a portion of completed
 * requests, so seeded data exercises the full Maintenance→Expense→
 * Accounting chain too, not just the standalone records.
 */
class MaintenanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(MaintenanceRequestService::class);

        Company::query()->each(function (Company $company) use ($service): void {
            $properties = Property::query()->where('company_id', $company->id)->get();

            if ($properties->isEmpty()) {
                return;
            }

            $units = Unit::query()->where('company_id', $company->id)->get();
            $tenants = Tenant::query()->where('company_id', $company->id)->where('status', '!=', 'blacklisted')->get();
            $vendors = Vendor::query()->where('company_id', $company->id)->where('status', 'active')->get();
            $expenseAccounts = Account::query()->where('company_id', $company->id)->where('type', 'expense')->get();

            for ($i = 0; $i < random_int(4, 8); $i++) {
                $property = $properties->random();
                $outcome = fake()->randomElement(['open', 'open', 'in_progress', 'completed', 'completed', 'cancelled']);
                $vendor = $vendors->isNotEmpty() && fake()->boolean(60) ? $vendors->random() : null;

                $factory = match ($outcome) {
                    'in_progress' => MaintenanceRequest::factory()->inProgress(),
                    'completed' => MaintenanceRequest::factory()->completed(),
                    'cancelled' => MaintenanceRequest::factory()->cancelled(),
                    default => MaintenanceRequest::factory(),
                };

                $request = $factory
                    ->create([
                        'company_id' => $company->id,
                        'property_id' => $property->id,
                        'unit_id' => $units->where('company_id', $company->id)->isNotEmpty() && fake()->boolean(60)
                            ? $units->random()->id
                            : null,
                        'tenant_id' => $tenants->isNotEmpty() && fake()->boolean(50) ? $tenants->random()->id : null,
                        'vendor_id' => $vendor?->id,
                        'estimated_cost' => fake()->boolean(70) ? fake()->randomFloat(2, 30, 500) : null,
                    ]);

                if ($outcome === 'completed' && $vendor && $expenseAccounts->isNotEmpty() && fake()->boolean(50)) {
                    $service->recordExpense($request, [
                        'account_id' => $expenseAccounts->random()->id,
                        'amount' => fake()->randomFloat(2, 30, 500),
                        'expense_date' => $request->completed_date->toDateString(),
                        'payment_status' => 'paid',
                        'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_money']),
                        'paid_on' => $request->completed_date->toDateString(),
                    ]);
                }
            }
        });
    }
}
