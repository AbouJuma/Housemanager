<?php

namespace App\Modules\VisitorManagement\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\VisitorManagement\Models\Visitor;
use App\Modules\VisitorManagement\Policies\VisitorPolicy;
use App\Modules\VisitorManagement\Repositories\EloquentVisitorRepository;
use App\Modules\VisitorManagement\Repositories\VisitorRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class VisitorManagementServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VisitorRepositoryInterface::class, EloquentVisitorRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Visitor::class, VisitorPolicy::class);
    }
}
