<?php

namespace App\Modules\Users\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Models\User;
use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\Users\Listeners\SeedDefaultRolesForCompany;
use App\Modules\Users\Policies\RolePolicy;
use App\Modules\Users\Policies\UserPolicy;
use App\Modules\Users\Repositories\EloquentUserRepository;
use App\Modules\Users\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UsersServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Event::listen(CompanyCreated::class, SeedDefaultRolesForCompany::class);
    }
}
