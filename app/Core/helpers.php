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

if (! function_exists('company_timezone')) {
    /**
     * The signed-in user's company timezone (staff, tenant, landlord or
     * buyer alike), falling back to config('company.default_timezone')
     * - East Africa Time - for the SaaS owner or a company without one.
     */
    function company_timezone(): string
    {
        $timezone = \App\Modules\Companies\Support\BrandTheme::current()->timezone;

        return $timezone && in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('company.default_timezone');
    }
}

if (! function_exists('company_now')) {
    /**
     * "Now" as the company sees it - for greetings ("Good afternoon")
     * and dates shown to people. Stored timestamps stay in app timezone.
     */
    function company_now(): \Illuminate\Support\Carbon
    {
        return now()->setTimezone(company_timezone());
    }
}

if (! function_exists('same_id')) {
    /**
     * Whether two database ids are the same record. Some MySQL drivers
     * (e.g. libmysqlclient-based pdo_mysql on shared hosting) return
     * foreign keys as strings while Eloquent casts primary keys to int,
     * so `$lease->tenant_id === $tenant->id` would wrongly be false.
     */
    function same_id(mixed $a, mixed $b): bool
    {
        return $a !== null && $b !== null && (string) $a === (string) $b;
    }
}
