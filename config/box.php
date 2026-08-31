<?php

return [
    /**
     * Authentication Method to use when interacting with BOX.
     * For more information see: https://developer.box.com/guides/authentication/select/
     * Currently supported methods: 'app_token', 'client_credentials'
     * app_token method requires the `private_key.pem` file at your application root level.
     */
    'auth_method' => env('BOX_AUTH_METHOD', 'app_token'),

    /*
    |--------------------------------------------------------------------------
    | Box Developer IDs
    |--------------------------------------------------------------------------
    |
    | Set these value based on this documentation
    | https://developer.box.com/guides/authentication/jwt/jwt-setup/
    |
    */

    'client_id' => env('BOX_CLIENT_ID', null),
    'client_secret' => env('BOX_CLIENT_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Get Enterprise IDs
    |--------------------------------------------------------------------------
    |
    | Login into box.com and go to admin console menu on top left.
    | Click gear icon on top right, click Enterprise or Business Setting.
    | See the enterprise id on the screen
    |
    */

    'enterprise_id' => env('BOX_ENTERPRISE_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Expiration Time for Access Token
    |--------------------------------------------------------------------------
    |
    | use this in terminal openssl genrsa -aes256 -out private_key.pem 2048
    | follow documentation here https://box-content.readme.io/docs/app-auth
    | copy this file in root folder of Laravel 5 project
    |
    */

    'public_key_id' => env('BOX_KEY_ID', null),
    'private_key' => base_path().'/private_key.pem',
    'passphrase' => env('BOX_KEY_PASSWORD', null),

    /*
    |--------------------------------------------------------------------------
    | Access Token Cache
    |--------------------------------------------------------------------------
    |
    | Access tokens are cached and reused until they are close to expiring.
    | The buffer prevents using a token that is about to expire mid-request.
    |
    */

    'token_cache_key' => env('BOX_TOKEN_CACHE_KEY', 'box.access_token'),
    'token_expiry_buffer' => (int) env('BOX_TOKEN_EXPIRY_BUFFER', 60),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Shared HTTP settings used for Box API requests. Retries are disabled
    | by default and can be enabled when transient upstream failures are
    | expected in your environment.
    |
    */

    'request_timeout' => (int) env('BOX_REQUEST_TIMEOUT', 30),
    'request_retry_times' => (int) env('BOX_REQUEST_RETRY_TIMES', 0),
    'request_retry_sleep' => (int) env('BOX_REQUEST_RETRY_SLEEP', 100),

    /*
    |--------------------------------------------------------------------------
    | BOX ROOT FOLDER ID
    |--------------------------------------------------------------------------
    | This is the folder id of the root folder where transactions will occur.
    | If Not provided, the root folder is considered as 0.
     */
    'folder_id' => env('BOX_FOLDER_ID', 0),
];
