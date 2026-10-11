<?php

namespace App\Providers;

use App\Core\Foundation\ModuleManager;
use App\Core\Tenancy\CompanyContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CompanyContext::class);

        ModuleManager::register($this->app);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn ($user, string $ability) => $user->isSaasOwner() && in_array($ability, ['reports.view', 'message-logs.view'], true) ? true : null);

        Paginator::useBootstrapFive();
    }
}
