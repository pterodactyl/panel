<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Signing Key
    |--------------------------------------------------------------------------
    |
    | This key is used for the verification of JSON Web Tokens in flight and
    | should be different than the application encryption key. This key should
    | be kept private at all times.
    |
    */
    'key' => env('APP_JWT_KEY'),
    'lifetime' => env('APP_JWT_LIFETIME', 1440),

    'signer' => \Lcobucci\JWT\Signer\Hmac\Sha256::class,
];
