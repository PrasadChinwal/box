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
];
