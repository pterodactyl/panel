<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hashids Configuration
    |--------------------------------------------------------------------------
    |
    | Here are the settings that control the Hashids setup and usage in the panel.
    |
    */
    'salt' => env('HASHIDS_SALT'),
    'length' => env('HASHIDS_LENGTH', 8),
    'alphabet' => env('HASHIDS_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
];
