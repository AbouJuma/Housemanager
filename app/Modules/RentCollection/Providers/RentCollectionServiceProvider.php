<?php

namespace App\Modules\RentCollection\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\RentCollection\Console\Commands\GenerateRentInvoices;
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

        $this->commands([GenerateRentInvoices::class, SendRentReminders::class, VerifyPendingRentPayments::class]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            // GenerateRentInvoices first, so a lease invoice generated
            // today is immediately eligible for SendRentReminders'/
            // MarkOverdueInvoices' same-day checks - then those two
            // (MarkOverdueInvoices lives in the Invoices module, also
            // daily), then the reminder itself.
            // These two text customers (a new-invoice message, rent
            // reminders), so they run at a sensible local hour: app.timezone
            // is UTC, and a bare daily() (00:00 UTC) is 3 AM in East Africa.
            // MarkOverdueInvoices still runs at 00:00 UTC, before both.
            $schedule->command(GenerateRentInvoices::class)->dailyAt('08:00')->timezone('Africa/Dar_es_Salaam');
            $schedule->command(SendRentReminders::class)->dailyAt('08:10')->timezone('Africa/Dar_es_Salaam');
            $schedule->command(VerifyPendingRentPayments::class)->everyFifteenMinutes();
        });
    }
}
