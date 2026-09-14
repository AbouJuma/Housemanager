<?php

namespace App\Modules\Companies\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Policies\CompanyPolicy;
use App\Modules\Companies\Repositories\CompanyRepositoryInterface;
use App\Modules\Companies\Repositories\EloquentCompanyRepository;
use Illuminate\Support\Facades\Gate;

class CompaniesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyRepositoryInterface::class, EloquentCompanyRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Company::class, CompanyPolicy::class);
    }
}
