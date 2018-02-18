<?php

return [
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Root path where theme Views will be located.
    | Can be outside default views path e.g.: resources/themes
    | Leave it null if you will put your themes in the default views folder
    | (as defined in config\views.php)
    |--------------------------------------------------------------------------
    */
    'themes_path' => realpath(base_path('resources/themes')),

    /*
    |--------------------------------------------------------------------------
    | Set behavior if an asset is not found in a Theme hierarchy.
    | Available options: THROW_EXCEPTION | LOG_ERROR | IGNORE
    |--------------------------------------------------------------------------
    */
    'asset_not_found' => 'LOG_ERROR',

    /*
    |--------------------------------------------------------------------------
    | Do we want a theme activated by default? Can be set at runtime with:
    | Theme::set('theme-name');
    |--------------------------------------------------------------------------
    */
    'default' => env('APP_THEME', 'pterodactyl'),

    /*
    |--------------------------------------------------------------------------
    | Cache theme.json configuration files that are located in each theme's folder
    | in order to avoid searching theme settings in the filesystem for each request
    |--------------------------------------------------------------------------
    */
    'cache' => true,

    /*
    |--------------------------------------------------------------------------
    | Define available themes. Format:
    |
    | 	'theme-name' => [
    | 		'extends'       => 'theme-to-extend',  // optional
    | 		'views-path'    => 'path-to-views',    // defaults to: resources/views/theme-name
    | 		'asset-path'    => 'path-to-assets',   // defaults to: public/theme-name
    |
    |		// You can add your own custom keys
    |		// Use Theme::getSetting('key') & Theme::setSetting('key', 'value') to access them
    | 		'key'           => 'value',
    | 	],
    |
    |--------------------------------------------------------------------------
    */
    'themes' => [
        'pterodactyl' => [
            'extends' => null,
            'views-path' => 'pterodactyl',
            'asset-path' => 'themes/pterodactyl',
        ],
    ],
];
