<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Platform-wide SMS fallback used by App\Modules\Sms\Services\
    // SmsManager when a company has no active SMS Integration of its
    // own configured (see Module 7's Integrations Center) - no
    // committed default secret; unset means the fallback simply isn't
    // available rather than silently sending under a shared key.
    'beems' => [
        'api_key' => env('BEEMS_API_KEY'),
        'secret_key' => env('BEEMS_SECRET_KEY'),
        'sender_name' => env('BEEMS_SENDER_NAME', 'DocTouch'),
    ],

];
