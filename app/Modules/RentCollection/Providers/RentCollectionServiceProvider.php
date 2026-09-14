<?php

namespace App\Modules\RentCollection\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\RentCollection\Console\Commands\SendRentReminders;
use App\Modules\RentCollection\Console\Commands\VerifyPendingRentPayments;
use Illuminate\Console\Scheduling\Schedule;

/**
 * No Gate::policy() registration - this module adds no domain entity
 * of its own with independent CRUD; InvoicePaymentAttempt is a pure
 * audit/child record authorized through Invoice's existing policy in
 * the controllers (the same treatment InvoiceItem/InvoicePayment get
 * from InvoicePolicy, ARCHITECTURE.md §10e/§10f).
 */
class RentCollectionServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        parent::boot();

        $this->commands([SendRentReminders::class, VerifyPendingRentPayments::class]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            // After MarkOverdueInvoices (Invoices module, also daily)
            // so a freshly-overdue invoice gets its reminder same-day.
            $schedule->command(SendRentReminders::class)->daily();
            $schedule->command(VerifyPendingRentPayments::class)->everyFifteenMinutes();
        });
    }
}
