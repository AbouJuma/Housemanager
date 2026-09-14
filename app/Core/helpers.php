<?php

use App\Modules\Settings\Services\SettingsService;

if (! function_exists('setting')) {
    /**
     * Read a company setting for whichever company is currently in
     * context (see App\Modules\Settings\Services\SettingsService::
     * forCurrentCompany). Intended for Blade views/controllers that
     * just want "this company's value" without resolving the service
     * themselves.
     */
    function setting(string $key): mixed
    {
        return app(SettingsService::class)->forCurrentCompany($key);
    }
}
