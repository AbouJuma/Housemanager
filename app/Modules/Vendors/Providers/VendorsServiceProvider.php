<?php

namespace App\Modules\Vendors\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\Vendors\Policies\VendorPolicy;
use App\Modules\Vendors\Repositories\EloquentVendorRepository;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class VendorsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VendorRepositoryInterface::class, EloquentVendorRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Vendor::class, VendorPolicy::class);
    }
}
