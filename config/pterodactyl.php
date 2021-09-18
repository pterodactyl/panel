<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Restricted Environment
    |--------------------------------------------------------------------------
    |
    | Set this environment variable to true to enable a restricted configuration
    | setup on the panel. When set to true, configurations stored in the
    | database will not be applied.
    */
    'load_environment_only' => (bool) env('APP_ENVIRONMENT_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Service Author
    |--------------------------------------------------------------------------
    |
    | Each panel installation is assigned a unique UUID to identify the
    | author of custom services, and make upgrades easier by identifying
    | standard Pterodactyl shipped services.
    */
    'service' => [
        'author' => env('APP_SERVICE_AUTHOR', 'unknown@unknown.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Should login success and failure events trigger an email to the user?
    */
    'auth' => [
        '2fa_required' => env('APP_2FA_REQUIRED', 0),
        '2fa' => [
            'bytes' => 32,
            'window' => env('APP_2FA_WINDOW', 4),
            'verify_newer' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Certain pagination result counts can be configured here and will take
    | effect globally.
    */
    'paginate' => [
        'frontend' => [
            'servers' => env('APP_PAGINATE_FRONT_SERVERS', 15),
        ],
        'admin' => [
            'servers' => env('APP_PAGINATE_ADMIN_SERVERS', 25),
            'users' => env('APP_PAGINATE_ADMIN_USERS', 25),
        ],
        'api' => [
            'nodes' => env('APP_PAGINATE_API_NODES', 25),
            'servers' => env('APP_PAGINATE_API_SERVERS', 25),
            'users' => env('APP_PAGINATE_API_USERS', 25),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Options
    |--------------------------------------------------------------------------
    |
    | Configuration options for the API.
    */
    'api' => [
        'include_on_list' => env('API_INCLUDE_ON_LIST', false),
        'key_expire_time' => env('API_KEY_EXPIRE_TIME', 60 * 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guzzle Connections
    |--------------------------------------------------------------------------
    |
    | Configure the timeout to be used for Guzzle connections here.
    */
    'guzzle' => [
        'timeout' => env('GUZZLE_TIMEOUT', 30),
        'connect_timeout' => env('GUZZLE_CONNECT_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Names
    |--------------------------------------------------------------------------
    |
    | Configure the names of queues to be used in the database.
    */
    'queues' => [
        'low' => env('QUEUE_LOW', 'low'),
        'standard' => env('QUEUE_STANDARD', 'standard'),
        'high' => env('QUEUE_HIGH', 'high'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Timers
    |--------------------------------------------------------------------------
    |
    | The amount of time in minutes before performing certain actions on the system.
    */
    'tasks' => [
        'clear_log' => env('PTERODACTYL_CLEAR_TASKLOG', 720),
        'delete_server' => env('PTERODACTYL_DELETE_MINUTES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN
    |--------------------------------------------------------------------------
    |
    | Information for the panel to use when contacting the CDN to confirm
    | if panel is up to date.
    */
    'cdn' => [
        'cache_time' => 60,
        'url' => 'https://cdn.pterodactyl.io/releases/latest.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Features
    |--------------------------------------------------------------------------
    |
    | Allow clients to create their own databases.
    */
    'client_features' => [
        'databases' => [
            'enabled' => env('PTERODACTYL_CLIENT_DATABASES_ENABLED', true),
            'allow_random' => env('PTERODACTYL_CLIENT_DATABASES_ALLOW_RANDOM', true),
        ],

        'schedules' => [
            // The total number of tasks that can exist for any given schedule at once.
            'per_schedule_task_limit' => env('PTERODACTYL_PER_SCHEDULE_TASK_LIMIT', 10),
        ],

        'allocations' => [
            'enabled' => env('PTERODACTYL_CLIENT_ALLOCATIONS_ENABLED', false),
            'range_start' => env('PTERODACTYL_CLIENT_ALLOCATIONS_RANGE_START'),
            'range_end' => env('PTERODACTYL_CLIENT_ALLOCATIONS_RANGE_END'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Editor
    |--------------------------------------------------------------------------
    |
    | This array includes the MIME filetypes that can be edited via the web.
    */
    'files' => [
        'max_edit_size' => env('PTERODACTYL_FILES_MAX_EDIT_SIZE', 1024 * 1024 * 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Response Routes
    |--------------------------------------------------------------------------
    |
    | You should not edit this block. These routes are ajax based routes that
    | expect content to be returned in JSON format.
    */
    'json_routes' => [
        'api/*',
        'daemon/*',
        'remote/*',
    ],

    'default_api_version' => 'application/vnd.pterodactyl.v1+json',

    /*
    |--------------------------------------------------------------------------
    | Dynamic Environment Variables
    |--------------------------------------------------------------------------
    |
    | Place dynamic environment variables here that should be auto-appended
    | to server environment fields when the server is created or updated.
    |
    | Items should be in 'key' => 'value' format, where key is the environment
    | variable name, and value is the server-object key. For example:
    |
    | 'P_SERVER_CREATED_AT' => 'created_at'
    */
    'environment_variables' => [
        'P_SERVER_ALLOCATION_LIMIT' => 'allocation_limit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Verification
    |--------------------------------------------------------------------------
    |
    | This section controls the output format for JS & CSS assets.
    */
    'assets' => [
        'use_hash' => env('PTERODACTYL_USE_ASSET_HASH', false),
    ],
];
