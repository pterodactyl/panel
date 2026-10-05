<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limits
    |--------------------------------------------------------------------------
    |
    | Defines the rate limit for the number of requests per minute that can be
    | executed against both the client and internal (application) APIs over the
    | defined period (by default, 1 minute).
    |
    */
    'rate_limit' => [
        'client_period' => 1,
        'client' => env('APP_API_CLIENT_RATELIMIT', 256),

        'application_period' => 1,
        'application' => env('APP_API_APPLICATION_RATELIMIT', 256),

        'admin_period' => 1,
        'admin' => env('APP_API_ADMIN_RATELIMIT', 256),
    ],
];
