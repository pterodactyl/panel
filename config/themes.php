<?php

return [
    'enabled' => true,
    'themes_path' => realpath(base_path('resources/themes')),
    'asset_not_found' => 'LOG_ERROR',
    'active' => env('APP_THEME', 'pterodactyl'),

    'themes' => [
        'pterodactyl' => [
            'extends'       => null,
            'views-path'    => 'pterodactyl',
            'asset-path'    => 'themes/pterodactyl',
        ],
    ],
];
