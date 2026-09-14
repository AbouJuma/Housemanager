<?php

namespace App\Modules\Settings\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Settings\Policies\SettingsPolicy;
use Illuminate\Support\Facades\Gate;

class SettingsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        parent::boot();

        // Not Gate::policy(): the settings pages aren't authorized
        // against a specific model instance, so each policy method is
        // registered as its own named ability instead.
        Gate::define('settings.view-company', [SettingsPolicy::class, 'viewCompany']);
        Gate::define('settings.update-company', [SettingsPolicy::class, 'updateCompany']);
        Gate::define('settings.view-system', [SettingsPolicy::class, 'viewSystem']);
        Gate::define('settings.update-system', [SettingsPolicy::class, 'updateSystem']);
    }
}
