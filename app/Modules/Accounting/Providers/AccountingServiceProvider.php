<?php

namespace App\Modules\Accounting\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Accounting\Listeners\PostInvoicePaymentJournalEntry;
use App\Modules\Accounting\Listeners\PostInvoiceRevenueJournalEntry;
use App\Modules\Accounting\Listeners\SeedDefaultChartOfAccountsForCompany;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Policies\AccountPolicy;
use App\Modules\Accounting\Policies\JournalEntryPolicy;
use App\Modules\Accounting\Repositories\AccountRepositoryInterface;
use App\Modules\Accounting\Repositories\EloquentAccountRepository;
use App\Modules\Accounting\Repositories\EloquentJournalEntryRepository;
use App\Modules\Accounting\Repositories\JournalEntryRepositoryInterface;
use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\Invoices\Events\InvoicePaymentRecorded;
use App\Modules\Invoices\Events\InvoiceSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class AccountingServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, EloquentAccountRepository::class);
        $this->app->bind(JournalEntryRepositoryInterface::class, EloquentJournalEntryRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);

        Event::listen(CompanyCreated::class, SeedDefaultChartOfAccountsForCompany::class);
        Event::listen(InvoiceSent::class, PostInvoiceRevenueJournalEntry::class);
        Event::listen(InvoicePaymentRecorded::class, PostInvoicePaymentJournalEntry::class);
    }
}
