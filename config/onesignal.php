<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OneSignal App ID
    |--------------------------------------------------------------------------
    */
    'app_id' => env('ONESIGNAL_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | REST API URL & Keys
    |--------------------------------------------------------------------------
    */
    'rest_api_url' => env('ONESIGNAL_REST_API_URL', 'https://api.onesignal.com'),
    'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
    'organization_api_key' => env('ONESIGNAL_ORGANIZATION_API_KEY', env('USER_AUTH_KEY')),

    // Legacy fallback - use organization_api_key instead
    'user_auth_key' => env('USER_AUTH_KEY'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */
    'guzzle_client_timeout' => env('ONESIGNAL_GUZZLE_CLIENT_TIMEOUT', 30),
    'max_retries' => env('ONESIGNAL_MAX_RETRIES', 2),
    'retry_delay' => env('ONESIGNAL_RETRY_DELAY', 500),
];
