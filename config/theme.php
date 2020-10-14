<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    */
    'default' => env('APP_THEME', NULL),


    /*
    |--------------------------------------------------------------------------
    | Theme Structure - Below is the mandatory theme structure
    |  Views go into /resources/themes/theme-name
    |  Scripts go into /resources/themes/theme-name/scripts
    |  Manifest.json goes in /resources/themes/theme-name/scripts
    |  Assets go into /public/themes/theme-name
    |--------------------------------------------------------------------------
    */

    'themes' => [
        'pterodactyl',
        'pterodactyly',
    ]
];
