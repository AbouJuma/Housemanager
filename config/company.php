<?php

return [
    /*
    | Timezone used for greetings, "today" and displayed dates when the
    | signed-in user's company hasn't set one (and for the SaaS owner).
    | Storage stays in config('app.timezone').
    */
    'default_timezone' => env('APP_DISPLAY_TIMEZONE', 'Africa/Dar_es_Salaam'),

    // Offered on Settings -> Company Profile.
    'currencies' => [
        'TZS' => 'Tanzanian Shilling (TZS)',
        'KES' => 'Kenyan Shilling (KES)',
        'UGX' => 'Ugandan Shilling (UGX)',
        'RWF' => 'Rwandan Franc (RWF)',
        'BIF' => 'Burundian Franc (BIF)',
        'USD' => 'US Dollar (USD)',
        'EUR' => 'Euro (EUR)',
        'GBP' => 'British Pound (GBP)',
        'ZAR' => 'South African Rand (ZAR)',
    ],

    // Shown first in the timezone picker; every other zone follows.
    'common_timezones' => [
        'Africa/Dar_es_Salaam' => 'East Africa Time - Dar es Salaam (UTC+3)',
        'Africa/Nairobi' => 'East Africa Time - Nairobi (UTC+3)',
        'Africa/Kampala' => 'East Africa Time - Kampala (UTC+3)',
        'Africa/Kigali' => 'Central Africa Time - Kigali (UTC+2)',
        'Africa/Johannesburg' => 'South Africa (UTC+2)',
        'Africa/Lagos' => 'West Africa (UTC+1)',
        'UTC' => 'UTC',
    ],
];
