<?php

namespace App\Modules\RentCollection\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use Illuminate\Database\Seeder;

/**
 * No seeded company has real payment gateway credentials (Integrations
 * has no seeder/factory of its own - there's nothing realistic to
 * charge against), so this creates InvoicePaymentAttempt rows directly
 * rather than through RentCollectionService::chargeInvoice(), which
 * would just reject every attempt with "no active payment provider."
 * A 'successful' attempt still goes through InvoiceService::recordPayment()
 * for the matching InvoicePayment/status update, the same real-business-
 * logic reasoning InvoiceSeeder uses (ARCHITECTURE.md §10e/§10f).
 */
class RentCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $invoiceService = app(InvoiceService::class);

        Company::query()->each(function (Company $company) use ($invoiceService): void {
            $candidates = Invoice::query()
                ->where('company_id', $company->id)
                ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                ->inRandomOrder()
                ->limit(3)
                ->get();

            foreach ($candidates as $invoice) {
                $outcome = fake()->randomElement(['pending', 'successful', 'failed']);
                $amountMinor = (int) round($invoice->balanceDue() * 100);

                if ($outcome === 'pending') {
                    InvoicePaymentAttempt::factory()->create([
                        'company_id' => $company->id,
                        'invoice_id' => $invoice->id,
                        'amount_minor' => $amountMinor,
                        'currency' => $company->currency_code,
                    ]);

                    continue;
                }

                if ($outcome === 'failed') {
                    InvoicePaymentAttempt::factory()->failed()->create([
                        'company_id' => $company->id,
                        'invoice_id' => $invoice->id,
                        'amount_minor' => $amountMinor,
                        'currency' => $company->currency_code,
                    ]);

                    continue;
                }

                $attempt = InvoicePaymentAttempt::factory()->successful()->create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'amount_minor' => $amountMinor,
                    'currency' => $company->currency_code,
                ]);

                $invoiceService->recordPayment($invoice, [
                    'amount' => $attempt->amountMajor(),
                    'paid_on' => now()->toDateString(),
                    'method' => 'gateway',
                    'reference' => $attempt->reference,
                    'notes' => "Collected via {$attempt->provider}.",
                ]);
            }
        });
    }
}
