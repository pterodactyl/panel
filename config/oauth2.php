<?php

return [

    'enabled' => env('OAUTH2', false),

    'required' => env('OAUTH2_REQUIRED', 0),

    /*
     * List of all installed drivers
     */
    'all_drivers' => env('OAUTH2_ALL_DRIVERS', 'github,facebook,twitter,linkedin,google,gitlab,bitbucket,discord'),

    /*
     * Provider configuration
     * Used to preinstall settings into the database
     */
    'providers' => [
        'github' => [
            'widget_html' => '',
            'widget_css' => '',
        ],
        'facebook' => [
            'widget_html' => '',
            'widget_css' => '',
        ],
        'twitter' => [
            'widget_html' => '',
            'widget_css' => '',
        ],
        'linkedin' => [
            'widget_html' => '',
            'widget_css' => '',
        ],
        'google' => [
            'widget_html' => '',
            'widget_css' => '',
        ],
        'gitlab' => [
            'widget_html' => '',
            'widget_css' => '',
        ],
        'bitbucket' => [
            'widget_html' => '',
            'widget_css' => '',
        ],
        'discord' => [
            'package' => 'socialiteproviders/discord',
            'listener' => 'SocialiteProviders\Discord\DiscordExtendSocialite@handle',
            'widget_html' => '',
            'widget_css' => '',
        ],
    ],

    /*
     * Default driver
     * Used as a fallback when trying to use a disabled/unset driver
     */
    'default_driver' => env('OAUTH2_DEFAULT_DRIVER', 'github'),

];
