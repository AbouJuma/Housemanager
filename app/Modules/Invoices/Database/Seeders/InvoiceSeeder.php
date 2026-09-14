<?php

namespace App\Modules\Invoices\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Leases\Models\Lease;
use Illuminate\Database\Seeder;

/**
 * Unlike LeaseSeeder/LandlordSeeder, this goes through InvoiceService
 * rather than mutating Eloquent directly - invoice_number generation
 * and total_amount computation are real business logic (ARCHITECTURE.md
 * §10e), not something a seeder should reimplement or bypass.
 */
class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(InvoiceService::class);

        Company::query()->each(function (Company $company) use ($service): void {
            $recordedById = $company->users()->first()?->id;

            Lease::query()->where('company_id', $company->id)->get()->each(function (Lease $lease) use ($service, $company, $recordedById): void {
                $monthsBack = random_int(1, 3);

                for ($i = $monthsBack; $i >= 1; $i--) {
                    $issueDate = now()->subMonths($i)->startOfMonth();

                    $invoice = $service->create([
                        'company_id' => $company->id,
                        'lease_id' => $lease->id,
                        'issue_date' => $issueDate->toDateString(),
                        'due_date' => $issueDate->copy()->addDays(14)->toDateString(),
                        'items' => [
                            ['description' => 'Rent - '.$issueDate->format('F Y'), 'quantity' => 1, 'unit_price' => (float) $lease->rent_amount],
                        ],
                    ]);

                    $outcome = fake()->randomElement(['draft', 'sent', 'paid', 'partially_paid', 'overdue']);

                    if ($outcome === 'draft') {
                        continue;
                    }

                    $service->send($invoice);

                    if ($outcome === 'sent') {
                        continue;
                    }

                    if ($outcome === 'overdue') {
                        $invoice->update(['status' => 'overdue']);

                        continue;
                    }

                    $paymentAmount = $outcome === 'paid'
                        ? (float) $invoice->total_amount
                        : round((float) $invoice->total_amount / 2, 2);

                    $service->recordPayment($invoice, [
                        'amount' => $paymentAmount,
                        'paid_on' => $issueDate->copy()->addDays(5)->toDateString(),
                        'method' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_money']),
                        'recorded_by_id' => $recordedById,
                    ]);
                }
            });
        });
    }
}
