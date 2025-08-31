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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rencana Kontrol API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for third party API integration for Rencana Kontrol
    | features. This includes BPJS API endpoints and authentication.
    |
    */

    'rencana_kontrol' => [
        'base_url' => env('RENCANA_KONTROL_API_URL', ),
        'cons_id' => env('RENCANA_KONTROL_CONS_ID'),
        'secret_key' => env('RENCANA_KONTROL_SECRET_KEY'),
        'user_key' => env('RENCANA_KONTROL_USER_KEY'),
        'timeout' => env('RENCANA_KONTROL_TIMEOUT', 30),
    ],
    /*
    |--------------------------------------------------------------------------
    | Apol API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for third party API integration for Apol
    | features. This includes BPJS API endpoints and authentication.
    |
    */
    'apol' => [
        'base_url' => env('APOL_BASE_URL', ),
        'cons_id' => env('APOL_CONS_ID'),
        'secret_key' => env('APOL_SECRET'),
        'user_key' => env('APOL_USER_KEY'),
        'apotek_ppk' => env('APOTEK_PPK', ''),
        'timeout' => env('APOL_TIMEOUT', 30),
    ],

];
