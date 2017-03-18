<?php

return [

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
        'core' => 'ptrdctyl-v040-11e6-8b77-86f30ca893d3',
        'author' => env('SERVICE_AUTHOR'),
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
            'servers' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Guzzle Connections
    |--------------------------------------------------------------------------
    |
    | Configure the timeout to be used for Guzzle connections here.
    */
    'guzzle' => [
        'timeout' => env('GUZZLE_TIMEOUT', 5),
        'connect_timeout' => env('GUZZLE_CONNECT_TIMEOUT', 3),
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
        'cache' => 60,
        'url' => 'https://cdn.pterodactyl.io/releases/latest.json',
    ],
];
