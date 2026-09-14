<?php

namespace App\Modules\Maintenance\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Maintenance\Policies\MaintenanceRequestPolicy;
use App\Modules\Maintenance\Repositories\EloquentMaintenanceRequestRepository;
use App\Modules\Maintenance\Repositories\MaintenanceRequestRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class MaintenanceServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MaintenanceRequestRepositoryInterface::class, EloquentMaintenanceRequestRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(MaintenanceRequest::class, MaintenanceRequestPolicy::class);
    }
}
