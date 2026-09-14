<?php

/*
|--------------------------------------------------------------------------
| Integrations Catalog
|--------------------------------------------------------------------------
|
| Every integration type and the providers listed under it in the
| Integrations Center. Adding a provider (or a whole new type) is a
| config change only - no migration, no new controller/view, since
| every provider shares the same credential form (see
| App\Modules\Integrations\Http\Controllers\IntegrationController).
|
| 'testers' maps a provider key to a class implementing
| App\Modules\Integrations\Contracts\IntegrationTester. Module 7 built
| the Integrations Center itself, not the gateway drivers; each Phase B
| module (Storage, Email, SMS, Payment) registers its own testers here
| as it lands. Until a tester is registered, "Test Connection" honestly
| reports that instead of faking a result.
|
| A type's 'field_labels' remaps the five generic credential field
| names (api_key/api_secret/merchant_id/username/password) to labels
| that actually make sense for that type - e.g. storage's "Merchant ID"
| is really a bucket name. The generic label is used when a type has no
| override. A provider's 'requires_credentials' (default true) hides
| the credentials card entirely for providers that need none, such as
| local disk storage - see App\Modules\Integrations\Http\Controllers\
| IntegrationController and the edit view.
|
*/

return [

    'types' => [
        'payment' => [
            'label' => 'Payments',
            'icon' => 'bi-credit-card',
            'providers' => [
                'stripe' => 'Stripe',
                'paypal' => 'PayPal',
                'flutterwave' => 'Flutterwave',
                'selcom' => 'Selcom',
                'nmb' => 'NMB',
                'crdb' => 'CRDB',
                'nbc' => 'NBC',
                'airtel_money' => 'Airtel Money',
                'mpesa' => 'M-Pesa',
                'tigo_pesa' => 'Tigo Pesa',
                'halo_pesa' => 'HaloPesa',
                'azampay' => 'AzamPay',
            ],
            // All twelve providers have a real driver - see each
            // class's own docblock in App\Modules\Payments\Drivers for
            // its exact credential mapping and confidence notes. Five
            // (Tigo Pesa, HaloPesa, NMB, CRDB, NBC) additionally read a
            // required Base URL out of the Password field, since this
            // app has no confirmed public hostname for them yet.
            'field_labels' => [
                'api_key' => 'API Key / Client ID / Public Key',
                'api_secret' => 'API Secret / Client Secret',
                'merchant_id' => 'Merchant ID / Vendor ID / Shortcode',
                'username' => 'Username / Passkey / Country Code (if required)',
                'password' => 'Password / Base URL Override (if required)',
            ],
        ],
        'sms' => [
            'label' => 'SMS',
            'icon' => 'bi-chat-dots',
            'providers' => [
                'beem' => 'Beem',
                'infobip' => 'Infobip',
                'twilio' => 'Twilio',
                'clicksend' => 'ClickSend',
                'africas_talking' => "Africa's Talking",
                'nextsms' => 'NextSMS',
            ],
            // Unlike Storage/Email, no shared transport exists for SMS -
            // each provider is a genuinely different HTTP API, so the
            // five generic fields mean something different per driver
            // (see each class in App\Modules\Sms\Drivers for its exact
            // mapping) rather than one uniform meaning across the type.
            'field_labels' => [
                'api_key' => 'API Key / Account SID / Username',
                'api_secret' => 'API Secret / Auth Token / Password',
                'merchant_id' => 'Sender ID (From)',
                'username' => 'Base URL (Infobip only, e.g. xxxxx.api.infobip.com)',
                'password' => 'Password (unused by most providers)',
            ],
        ],
        'email' => [
            'label' => 'Email',
            'icon' => 'bi-envelope',
            'providers' => [
                'smtp' => 'SMTP',
                'gmail' => 'Gmail',
                'microsoft365' => 'Microsoft 365',
                'mailgun' => 'Mailgun',
                'sendgrid' => 'SendGrid',
                'ses' => 'Amazon SES',
            ],
            // Every provider here is reachable over plain SMTP (Gmail/365
            // app passwords, Mailgun/SendGrid/SES SMTP credentials) - one
            // transport for all six means no extra composer packages
            // (aws-sdk-php, symfony/mailgun-mailer, ...) on top of what
            // Laravel already ships. See App\Modules\Email\Services\
            // EmailResolver for the well-known host/port/encryption
            // defaults used when Host is left blank.
            'field_labels' => [
                'api_key' => 'Host',
                'api_secret' => 'Port',
                'merchant_id' => 'Encryption (tls, ssl, or none)',
                'username' => 'Username',
                'password' => 'Password',
            ],
        ],
        'storage' => [
            'label' => 'Storage',
            'icon' => 'bi-hdd',
            'providers' => [
                'local' => 'Local Storage',
                's3' => 'Amazon S3',
                'r2' => 'Cloudflare R2',
                'spaces' => 'DigitalOcean Spaces',
                'gcs' => 'Google Cloud Storage',
            ],
            'providers_without_credentials' => ['local'],
            'field_labels' => [
                'api_key' => 'Access Key ID',
                'api_secret' => 'Secret Access Key',
                'merchant_id' => 'Bucket Name',
                'username' => 'Region',
                'password' => 'Custom Endpoint (R2/Spaces only)',
            ],
        ],
        'maps' => [
            'label' => 'Maps',
            'icon' => 'bi-map',
            'providers' => [
                'google_maps' => 'Google Maps',
                'mapbox' => 'Mapbox',
                'osm' => 'OpenStreetMap',
            ],
        ],
        'ai' => [
            'label' => 'AI',
            'icon' => 'bi-stars',
            'providers' => [
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic',
                'gemini' => 'Gemini',
                'deepseek' => 'DeepSeek',
                'xai' => 'xAI',
            ],
        ],
    ],

    'testers' => [
        'local' => App\Modules\Storage\Testers\LocalStorageTester::class,
        's3' => App\Modules\Storage\Testers\S3CompatibleTester::class,
        'r2' => App\Modules\Storage\Testers\S3CompatibleTester::class,
        'spaces' => App\Modules\Storage\Testers\S3CompatibleTester::class,
        'gcs' => App\Modules\Storage\Testers\GcsTester::class,
        'smtp' => App\Modules\Email\Testers\SmtpEmailTester::class,
        'gmail' => App\Modules\Email\Testers\SmtpEmailTester::class,
        'microsoft365' => App\Modules\Email\Testers\SmtpEmailTester::class,
        'mailgun' => App\Modules\Email\Testers\SmtpEmailTester::class,
        'sendgrid' => App\Modules\Email\Testers\SmtpEmailTester::class,
        'ses' => App\Modules\Email\Testers\SmtpEmailTester::class,
        'beem' => App\Modules\Sms\Drivers\BeemDriver::class,
        'infobip' => App\Modules\Sms\Drivers\InfobipDriver::class,
        'twilio' => App\Modules\Sms\Drivers\TwilioDriver::class,
        'clicksend' => App\Modules\Sms\Drivers\ClickSendDriver::class,
        'africas_talking' => App\Modules\Sms\Drivers\AfricasTalkingDriver::class,
        'nextsms' => App\Modules\Sms\Drivers\NextSmsDriver::class,
        'stripe' => App\Modules\Payments\Drivers\StripeDriver::class,
        'paypal' => App\Modules\Payments\Drivers\PayPalDriver::class,
        'flutterwave' => App\Modules\Payments\Drivers\FlutterwaveDriver::class,
        'selcom' => App\Modules\Payments\Drivers\SelcomDriver::class,
        'mpesa' => App\Modules\Payments\Drivers\MpesaDriver::class,
        'airtel_money' => App\Modules\Payments\Drivers\AirtelMoneyDriver::class,
        'azampay' => App\Modules\Payments\Drivers\AzamPayDriver::class,
        'tigo_pesa' => App\Modules\Payments\Drivers\TigoPesaDriver::class,
        'halo_pesa' => App\Modules\Payments\Drivers\HaloPesaDriver::class,
        'nmb' => App\Modules\Payments\Drivers\NmbDriver::class,
        'crdb' => App\Modules\Payments\Drivers\CrdbDriver::class,
        'nbc' => App\Modules\Payments\Drivers\NbcDriver::class,
        // Every Storage/Email/SMS/Payment provider now has a real
        // driver + tester. Maps and AI (Phase E) have none yet since
        // those modules aren't built.
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Drivers
    |--------------------------------------------------------------------------
    |
    | Every SMS provider's driver, keyed the same as 'providers' above -
    | the business capability (App\Modules\Sms\Services\SmsManager::send()).
    | Kept as its own map (rather than just reusing 'testers') since a
    | future provider could plausibly need a driver without also being
    | testable, even though every current SMS provider is both.
    |
    */

    'sms_drivers' => [
        'beem' => App\Modules\Sms\Drivers\BeemDriver::class,
        'infobip' => App\Modules\Sms\Drivers\InfobipDriver::class,
        'twilio' => App\Modules\Sms\Drivers\TwilioDriver::class,
        'clicksend' => App\Modules\Sms\Drivers\ClickSendDriver::class,
        'africas_talking' => App\Modules\Sms\Drivers\AfricasTalkingDriver::class,
        'nextsms' => App\Modules\Sms\Drivers\NextSmsDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Drivers
    |--------------------------------------------------------------------------
    |
    | Every payment provider's driver, keyed the same as 'providers'
    | above - the business capability (App\Modules\Payments\Services\
    | PaymentManager). All twelve now have a real driver; confidence
    | varies per provider (documented in each driver's own docblock) -
    | Stripe/PayPal/Flutterwave are built against extensively-referenced
    | public APIs, the mobile-money/bank drivers against the region's
    | de facto standard OAuth2-token-then-collection-request shape.
    | Tigo Pesa, HaloPesa, NMB, CRDB, and NBC additionally need a Base
    | URL supplied via their own credential form (see each driver) since
    | this app has no confirmed public hostname for them - everything
    | else about those five is real and ready, they're just waiting on
    | that one detail alongside real credentials.
    |
    */

    'payment_drivers' => [
        'stripe' => App\Modules\Payments\Drivers\StripeDriver::class,
        'paypal' => App\Modules\Payments\Drivers\PayPalDriver::class,
        'flutterwave' => App\Modules\Payments\Drivers\FlutterwaveDriver::class,
        'selcom' => App\Modules\Payments\Drivers\SelcomDriver::class,
        'mpesa' => App\Modules\Payments\Drivers\MpesaDriver::class,
        'airtel_money' => App\Modules\Payments\Drivers\AirtelMoneyDriver::class,
        'azampay' => App\Modules\Payments\Drivers\AzamPayDriver::class,
        'tigo_pesa' => App\Modules\Payments\Drivers\TigoPesaDriver::class,
        'halo_pesa' => App\Modules\Payments\Drivers\HaloPesaDriver::class,
        'nmb' => App\Modules\Payments\Drivers\NmbDriver::class,
        'crdb' => App\Modules\Payments\Drivers\CrdbDriver::class,
        'nbc' => App\Modules\Payments\Drivers\NbcDriver::class,
    ],

];
