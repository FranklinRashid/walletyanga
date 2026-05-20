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

    'paychangu' => [
        'public_key' => env('PAYCHANGU_PUBLIC_KEY'),
        'secret_key' => env('PAYCHANGU_SECRET_KEY'),
        'webhook_secret' => env('PAYCHANGU_WEBHOOK_SECRET'),
    ],

    'cards' => [
        'issuer' => env('CARD_ISSUER', 'sandbox'),
    ],

    'sudo' => [
        'base_url' => env('SUDO_BASE_URL', 'https://api.sandbox.sudo.cards'),
        'vault_url' => env('SUDO_VAULT_URL', 'https://vault.sandbox.sudo.cards'),
        'api_key' => env('SUDO_API_KEY'),
        'auth_scheme' => env('SUDO_AUTH_SCHEME', 'Bearer'),
        'card_currency' => env('SUDO_CARD_CURRENCY', 'USD'),
        'card_brand' => env('SUDO_CARD_BRAND', 'Visa'),
        'issuer_country' => env('SUDO_ISSUER_COUNTRY', 'USA'),
        'billing_country' => env('SUDO_BILLING_COUNTRY', 'MW'),
        'postal_code' => env('SUDO_POSTAL_CODE', '00000'),
        'account_type' => env('SUDO_ACCOUNT_TYPE', 'wallet'),
        'account_kind' => env('SUDO_ACCOUNT_KIND', 'Savings'),
        'default_daily_limit_minor' => (int) env('SUDO_DEFAULT_DAILY_LIMIT_MINOR', 5000),
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

];
