<?php

namespace App\Modules\Landlords\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Landlords\Policies\LandlordPolicy;
use App\Modules\Landlords\Repositories\EloquentLandlordRepository;
use App\Modules\Landlords\Repositories\LandlordRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class LandlordsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LandlordRepositoryInterface::class, EloquentLandlordRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Landlord::class, LandlordPolicy::class);
    }
}
