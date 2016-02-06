<?php

return [
    'enabled' => true,
    'themes_path' => realpath(base_path('resources/themes')),
    'asset_not_found' => 'LOG_ERROR',
    'active' => 'default',

    'themes' => [
        'default' => [
            'extends'       => null,
            'views-path'    => 'default',
            'asset-path'    => 'themes/default',
        ],
    ],
];
