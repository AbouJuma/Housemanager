<?php

namespace App\Modules\Leases\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Leases\Console\Commands\ExpireLeases;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Policies\LeasePolicy;
use App\Modules\Leases\Repositories\EloquentLeaseRepository;
use App\Modules\Leases\Repositories\LeaseRepositoryInterface;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;

class LeasesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LeaseRepositoryInterface::class, EloquentLeaseRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Lease::class, LeasePolicy::class);

        $this->commands([ExpireLeases::class]);

        // Scheduled here rather than in routes/console.php, so the
        // module stays self-contained (Module 12's precedent - see
        // NotificationsServiceProvider).
        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)->command(ExpireLeases::class)->daily();
        });
    }
}
