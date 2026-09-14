<?php

namespace App\Modules\Expenses\Database\Seeders;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Services\ExpenseService;
use App\Modules\Properties\Models\Property;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Seeder;

/**
 * Goes through ExpenseService, like InvoiceSeeder does for Invoices
 * (ARCHITECTURE.md §10e/§13) - posting the journal entry is real
 * business logic a seeder shouldn't reimplement or bypass.
 */
class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ExpenseService::class);

        Company::query()->each(function (Company $company) use ($service): void {
            $accounts = Account::query()->where('company_id', $company->id)->where('type', 'expense')->get();
            $properties = Property::query()->where('company_id', $company->id)->get();
            $vendors = Vendor::query()->where('company_id', $company->id)->get();

            if ($accounts->isEmpty()) {
                return;
            }

            for ($i = 0; $i < random_int(3, 6); $i++) {
                $paidNow = fake()->boolean(60);
                // About half the time this is tied to a formal Vendor
                // record; otherwise it's just free-text vendor_name -
                // both are realistic (ARCHITECTURE.md §14).
                $vendor = $vendors->isNotEmpty() && fake()->boolean(50) ? $vendors->random() : null;

                $attributes = [
                    'company_id' => $company->id,
                    'account_id' => $accounts->random()->id,
                    'property_id' => $properties->isNotEmpty() && fake()->boolean(70) ? $properties->random()->id : null,
                    'vendor_id' => $vendor?->id,
                    'vendor_name' => $vendor?->name ?? fake()->company(),
                    'description' => fake()->randomElement(['Plumbing repair', 'Electrical repair', 'Water bill', 'Electricity bill', 'Cleaning service', 'Pest control']),
                    'amount' => fake()->randomFloat(2, 20, 800),
                    'expense_date' => now()->subDays(random_int(0, 60))->toDateString(),
                    'payment_status' => $paidNow ? 'paid' : 'unpaid',
                ];

                if ($paidNow) {
                    $attributes['payment_method'] = fake()->randomElement(['cash', 'bank_transfer', 'mobile_money']);
                    $attributes['paid_on'] = $attributes['expense_date'];
                }

                $expense = $service->create($attributes);

                // A portion of the still-unpaid ones get settled later,
                // so seeded data exercises markAsPaid()'s settlement
                // posting too, not just the initial one.
                if (! $paidNow && fake()->boolean(40)) {
                    $service->markAsPaid($expense, [
                        'payment_method' => fake()->randomElement(['cash', 'bank_transfer']),
                        'paid_on' => now()->toDateString(),
                    ]);
                }
            }
        });
    }
}
