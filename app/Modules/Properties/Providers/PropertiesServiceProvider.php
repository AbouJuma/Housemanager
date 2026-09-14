<?php

namespace App\Modules\Properties\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Properties\Policies\BuildingPolicy;
use App\Modules\Properties\Policies\PropertyPolicy;
use App\Modules\Properties\Policies\UnitPolicy;
use App\Modules\Properties\Repositories\BuildingRepositoryInterface;
use App\Modules\Properties\Repositories\EloquentBuildingRepository;
use App\Modules\Properties\Repositories\EloquentPropertyRepository;
use App\Modules\Properties\Repositories\EloquentUnitRepository;
use App\Modules\Properties\Repositories\PropertyRepositoryInterface;
use App\Modules\Properties\Repositories\UnitRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class PropertiesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PropertyRepositoryInterface::class, EloquentPropertyRepository::class);
        $this->app->bind(BuildingRepositoryInterface::class, EloquentBuildingRepository::class);
        $this->app->bind(UnitRepositoryInterface::class, EloquentUnitRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(Building::class, BuildingPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);
    }
}
