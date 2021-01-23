<?php

use Pterodactyl\Helpers\Time;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'unix_socket' => env('DB_SOCKET'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'panel'),
            'username' => env('DB_USERNAME', 'pterodactyl'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => env('DB_PREFIX', ''),
            'strict' => env('DB_STRICT_MODE', false),
            'timezone' => env('DB_TIMEZONE', Time::getMySQLTimezoneOffset(env('APP_TIMEZONE', 'UTC'))),
        ],

        /*
        | -------------------------------------------------------------------------
        | Test Database Connection
        | -------------------------------------------------------------------------
        |
        | This connection is used by the integration and HTTP tests for Pterodactyl
        | development. Normal users of the Panel do not need to adjust any settings
        | in here.
        */
        'testing' => [
            'driver' => 'mysql',
            'host' => env('TESTING_DB_HOST', '127.0.0.1'),
            'port' => env('TESTING_DB_PORT', '3306'),
            'database' => env('TESTING_DB_DATABASE', 'panel_test'),
            'username' => env('TESTING_DB_USERNAME', 'pterodactyl_test'),
            'password' => env('TESTING_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'timezone' => env('DB_TIMEZONE', Time::getMySQLTimezoneOffset(env('APP_TIMEZONE', 'UTC'))),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [
        'client' => 'predis',

        'default' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'path' => env('REDIS_PATH', '/run/redis/redis.sock'),
            'host' => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
        ],

        'sessions' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'path' => env('REDIS_PATH', '/run/redis/redis.sock'),
            'host' => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE_SESSIONS', 1),
        ],
    ],
];
