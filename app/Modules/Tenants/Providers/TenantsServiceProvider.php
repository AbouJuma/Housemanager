<?php

namespace App\Modules\Tenants\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Policies\TenantPolicy;
use App\Modules\Tenants\Repositories\EloquentTenantRepository;
use App\Modules\Tenants\Repositories\TenantRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class TenantsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantRepositoryInterface::class, EloquentTenantRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Tenant::class, TenantPolicy::class);
    }
}
