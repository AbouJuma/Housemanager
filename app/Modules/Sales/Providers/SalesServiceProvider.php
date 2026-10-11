<?php

namespace App\Modules\Sales\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Sales\Console\Commands\BackfillInstallmentInvoices;
use App\Modules\Sales\Console\Commands\SendPaymentReminders;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Policies\BuyerPolicy;
use App\Modules\Sales\Policies\SalePolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;

class SalesServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Buyer::class, BuyerPolicy::class);

        $this->commands([SendPaymentReminders::class, BackfillInstallmentInvoices::class]);

        // Was never registered before - see SendPaymentReminders'
        // docblock/the messaging consolidation work. Scheduled here
        // rather than in routes/console.php, matching
        // RentCollectionServiceProvider's identical pattern for
        // SendRentReminders, so the module stays self-contained.
        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)->command(SendPaymentReminders::class)->dailyAt('08:10')->timezone('Africa/Dar_es_Salaam'); // customer SMS: not 3 AM local (see RentCollectionServiceProvider)
        });
    }
}
