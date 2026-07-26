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
    'otp' => [
        'channel' => env('OTP_CHANNEL', 'email'),
    ],

    'vonage' => [
        'api_key' => env('VONAGE_API_KEY'),
        'api_secret' => env('VONAGE_API_SECRET'),
        'from' => env('VONAGE_FROM', 'Data360'),
    ],

    'rnb' => [
        'base_url' => env('RNB_BASE_URL', 'https://rnb-api.beta.gouv.fr/api/alpha'),
        'from' => env('RNB_FROM'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'copropriete' => [
        'base_url'  => env('COPROPRIETE_BASE_URL', 'https://www.registre-coproprietes.gouv.fr/api/public'),
        'timeout'   => env('COPROPRIETE_TIMEOUT', 15),
        'cache_ttl' => env('COPROPRIETE_CACHE_TTL', 3600), // secondes (1h)
    ],
    'sigville' => [
        'url'      => env('SIGVILLE_API_URL', 'https://wsa.sig.ville.gouv.fr'),
        'username' => env('SIGVILLE_USERNAME', ''),
        'password' => env('SIGVILLE_PASSWORD', ''),
    ],
    'rne' => [
    'base_url' => env('RNE_API_URL', 'https://api.inpi.fr/v1'),
    'api_key'  => env('RNE_API_KEY'),
],


];
