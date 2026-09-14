<?php

namespace App\Modules\Invoices\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Invoices\Console\Commands\MarkOverdueInvoices;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Policies\InvoicePolicy;
use App\Modules\Invoices\Repositories\EloquentInvoiceRepository;
use App\Modules\Invoices\Repositories\InvoiceRepositoryInterface;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;

class InvoicesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Invoice::class, InvoicePolicy::class);

        $this->commands([MarkOverdueInvoices::class]);

        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)->command(MarkOverdueInvoices::class)->daily();
        });
    }
}
