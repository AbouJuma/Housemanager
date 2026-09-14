<?php

namespace App\Modules\Maps\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * Maps is an integration plumbing module. It has no controllers/views of
 * its own and exists so the Maps provider catalog appears in the
 * Integrations Center and the module can be discovered automatically.
 */
class MapsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }
}
