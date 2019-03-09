<?php

return [

    'enabled' => env('OAUTH2', false),

    'required' => env('OAUTH2_REQUIRED', 0),

    /**
     * List of all installed drivers
     */
    'all_drivers' => env('OAUTH2_ALL_DRIVERS', 'github,facebook,twitter,linkedin,google,gitlab,bitbucket,discord'),

    /**
     * Provider configuration
     * These settings will overwrite the generated ones
     */
    'providers' => [],

    /**
     * Default driver
     * Used as a fallback when trying to use a disabled/unset driver
     */
    'default_driver' => env('OAUTH2_DEFAULT_DRIVER', 'github'),

];