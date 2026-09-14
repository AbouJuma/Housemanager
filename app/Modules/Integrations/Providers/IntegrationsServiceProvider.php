<?php

namespace App\Modules\Integrations\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Integrations\Policies\IntegrationPolicy;
use App\Modules\Integrations\Repositories\EloquentIntegrationRepository;
use App\Modules\Integrations\Repositories\IntegrationRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class IntegrationsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IntegrationRepositoryInterface::class, EloquentIntegrationRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Integration::class, IntegrationPolicy::class);
    }
}
