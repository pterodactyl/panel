<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | First-party Extensions
    |--------------------------------------------------------------------------
    |
    | Extensions live under the "extensions/" directory at the panel root, each
    | in its own folder with an extension.json manifest. They register through
    | versioned runtime APIs - never by modifying core files - so they survive
    | panel updates and a broken extension is skipped instead of taking the panel
    | down.
    |
    */
    'enabled' => env('PTERODACTYL_EXTENSIONS_ENABLED', true),

    'panel_version' => config('app.version') === 'canary' ? '2.0.0-dev' : config('app.version'),
    'sdk_version' => '2.0.0-beta.4',
    'progress_retention_seconds' => 3600,

    // How long the result of a head tag callback (registerHeadTags) is cached; 0 disables it.
    'head_tags_cache_seconds' => 60,

    // Requests per minute each client may make to an extension's root path routes.
    'root_routes_per_minute' => 120,

    // Where extension packages are installed.
    'directory' => env('PTERODACTYL_EXTENSIONS_DIRECTORY', base_path('extensions')),

    // Where enabled extensions' built frontend assets are published.
    'assets_directory' => public_path('assets/extensions'),

    // The filesystem disk that holds files uploaded through `file` settings. They are
    // served by the panel from /extension-files, so the disk does not need to be public.
    'files_disk' => env('PTERODACTYL_EXTENSIONS_FILES_DISK', 'local'),
];
