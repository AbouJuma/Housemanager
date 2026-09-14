<?php

/*
|--------------------------------------------------------------------------
| Default Chart of Accounts
|--------------------------------------------------------------------------
|
| Seeded for every new company (see App\Modules\Accounting\Listeners\
| SeedDefaultChartOfAccountsForCompany, fired on Companies\Events\
| CompanyCreated - the same trigger Module 3 uses to seed default roles).
| Deliberately a modest starter set, not an exhaustive real-world chart -
| just enough to cover this app's own automatic postings (Cash, Bank,
| Accounts Receivable, Rental Income - see ARCHITECTURE.md §12) plus a
| reasonable set of categories for manual entries. Companies can add,
| rename, or deactivate accounts freely afterward; this is only the
| starting point.
|
| The 'code' values here are also referenced by name in
| App\Modules\Accounting\Listeners\PostInvoiceRevenueJournalEntry and
| PostInvoicePaymentJournalEntry - renaming a code here without updating
| those (or a company renumbering its own chart) doesn't break anything
| at the database level, but does mean the automatic postings won't find
| the account they're looking for (handled gracefully - see those
| listeners' docblocks).
|
*/

return [

    'default_chart' => [
        ['code' => '1000', 'name' => 'Cash', 'type' => 'asset'],
        ['code' => '1100', 'name' => 'Bank', 'type' => 'asset'],
        ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset'],
        ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
        ['code' => '2100', 'name' => 'Security Deposits Held', 'type' => 'liability'],
        ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity'],
        ['code' => '4000', 'name' => 'Rental Income', 'type' => 'income'],
        ['code' => '4100', 'name' => 'Late Fees Income', 'type' => 'income'],
        ['code' => '5000', 'name' => 'Maintenance Expense', 'type' => 'expense'],
        ['code' => '5100', 'name' => 'Utilities Expense', 'type' => 'expense'],
        ['code' => '5200', 'name' => 'Property Management Fees', 'type' => 'expense'],
        ['code' => '5900', 'name' => 'Bank Fees', 'type' => 'expense'],
    ],

];
