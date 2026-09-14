<?php

/*
|--------------------------------------------------------------------------
| Settings Catalog
|--------------------------------------------------------------------------
|
| Every readable/writable setting key, grouped by scope:
|   - 'system'  — SaaS-owner-only, company_id is NULL in the `settings`
|                  table (platform-wide defaults).
|   - 'company' — per-company, set by a company admin.
|
| Each entry declares a type (for casting on read) and a default (used
| when no row exists yet, so a setting works immediately without a
| seeder). Add a new key here and it's immediately gettable/settable
| through App\Modules\Settings\Services\SettingsService - no migration
| needed for the setting itself, only for the (shared) `settings` table.
|
*/

return [

    'system' => [
        'default_trial_days' => [
            'type' => 'integer',
            'default' => 14,
            'label' => 'Default Trial Length (days)',
            'description' => 'How many days a new company\'s trial lasts before it must be activated.',
        ],
        'default_currency_code' => [
            'type' => 'string',
            'default' => 'USD',
            'label' => 'Default Currency',
            'description' => 'ISO 4217 code assigned to a new company when none is given at creation.',
        ],
    ],

    'company' => [
        'date_format' => [
            'type' => 'string',
            'default' => 'M j, Y',
            'label' => 'Date Format',
            'description' => 'PHP date() format used wherever this company\'s dates are displayed.',
        ],
    ],

];
