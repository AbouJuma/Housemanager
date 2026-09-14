<?php

namespace App\Modules\AI\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * AI is an integration plumbing module. It has no controllers/views of
 * its own and exists so the AI provider catalog appears in the
 * Integrations Center and the module can be discovered automatically.
 */
class AIServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }
}
