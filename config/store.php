<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jexactyl Storefront Settings
    |--------------------------------------------------------------------------
    |
    | This configuration file is used to interact with the app in order to
    | get and set configurations for the Jexactyl Storefront.
    |
    */

    'enabled' => env('STORE_ENABLED', true),

    'enabled_slot' => env('STORE_ENABLED_SLOT', true),
    'enabled_cpu' => env('STORE_ENABLED_CPU', true),
    'enabled_memory' => env('STORE_ENABLED_MEMORY', true),
    'enabled_disk' => env('STORE_ENABLED_DISK', true),
];
