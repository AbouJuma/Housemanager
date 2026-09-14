<?php

namespace App\Modules\Subscriptions\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\Subscriptions\Console\Commands\ExpireSubscriptions;
use App\Modules\Subscriptions\Listeners\SeedDefaultTrialSubscription;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Policies\SubscriptionInvoicePolicy;
use App\Modules\Subscriptions\Policies\SubscriptionPlanPolicy;
use App\Modules\Subscriptions\Policies\SubscriptionPolicy;
use App\Modules\Subscriptions\Repositories\EloquentSubscriptionPlanRepository;
use App\Modules\Subscriptions\Repositories\SubscriptionPlanRepositoryInterface;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class SubscriptionsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriptionPlanRepositoryInterface::class, EloquentSubscriptionPlanRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(SubscriptionInvoice::class, SubscriptionInvoicePolicy::class);

        Event::listen(CompanyCreated::class, SeedDefaultTrialSubscription::class);

        $this->commands([ExpireSubscriptions::class]);

        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)->command(ExpireSubscriptions::class)->daily();
        });
    }
}
