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




    'geocodage' => [
        'base_url' => env('GEOCODAGE_BASE_URL', 'https://api-adresse.data.gouv.fr'),
    ],

    'cadastre' => [
        'base_url' => env('CADASTRE_BASE_URL', 'https://apicarto.ign.fr/api/cadastre'),
    ],

    'bdnb' => [
        'base_url' => env('BDNB_BASE_URL', 'https://api.bdnb.io'),
    ],

    'sirene' => [
        'base_url' => env('SIRENE_BASE_URL', 'https://api.insee.fr/entreprises/sirene/V3'),
        'token' => env('SIRENE_TOKEN'),
    ],

    'pappers' => [
        'base_url' => env('PAPPERS_BASE_URL', 'https://api.pappers.fr/v2'),
        'api_key' => env('PAPPERS_API_KEY'),
    ],

    'proxycheck' => [
        'api_key' => env('PROXYCHECK_API_KEY'),
    ],
    'twilio' => [
    'sid' => env('TWILIO_SID'),
    'token' => env('TWILIO_TOKEN'),
    'from' => env('TWILIO_FROM'),
],
];
