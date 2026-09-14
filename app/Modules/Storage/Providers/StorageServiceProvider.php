<?php

namespace App\Modules\Storage\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * Storage has no controllers/views of its own - it's pure plumbing
 * consumed by the Integrations Center (Module 7, "Test Connection" via
 * config('integrations.testers')) and by any module that writes a
 * company-owned file (Companies branding today; Documents, Media
 * exports, etc. later). This provider exists only so the module is
 * discovered and its migration (dropping companies.storage_disk) runs.
 */
class StorageServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }
}
