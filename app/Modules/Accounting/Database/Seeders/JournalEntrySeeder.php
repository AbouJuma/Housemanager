<?php

namespace App\Modules\Accounting\Database\Seeders;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\JournalEntryService;
use App\Modules\Companies\Models\Company;
use Illuminate\Database\Seeder;

/**
 * A couple of manual entries per company (not tied to an invoice) -
 * the automatic revenue/cash-receipt postings already come from
 * InvoiceSeeder/RentCollectionSeeder firing real InvoiceSent/
 * InvoicePaymentRecorded events (ARCHITECTURE.md §12); this covers the
 * other half of the module, a company admin posting something by hand
 * (an opening balance, a bank fee).
 */
class JournalEntrySeeder extends Seeder
{
    public function run(): void
    {
        $service = app(JournalEntryService::class);

        Company::query()->each(function (Company $company) use ($service): void {
            $cash = Account::query()->forCompany($company->id)->where('code', '1000')->first();
            $equity = Account::query()->forCompany($company->id)->where('code', '3000')->first();
            $bankFees = Account::query()->forCompany($company->id)->where('code', '5900')->first();
            $bank = Account::query()->forCompany($company->id)->where('code', '1100')->first();

            if (! $cash || ! $equity) {
                return;
            }

            $service->post([
                'company_id' => $company->id,
                'entry_date' => $company->created_at->addDay()->toDateString(),
                'reference' => 'Opening Balance',
                'description' => "Owner's initial capital contribution",
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 5000],
                    ['account_id' => $equity->id, 'credit' => 5000],
                ],
            ]);

            if ($bankFees && $bank) {
                $service->post([
                    'company_id' => $company->id,
                    'entry_date' => now()->subDays(random_int(1, 20))->toDateString(),
                    'reference' => null,
                    'description' => 'Monthly bank service charge',
                    'lines' => [
                        ['account_id' => $bankFees->id, 'debit' => 15],
                        ['account_id' => $bank->id, 'credit' => 15],
                    ],
                ]);
            }
        });
    }
}
