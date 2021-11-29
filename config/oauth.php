<?php

return [
    'enabled' => env('APP_OAUTH_ENABLED', false),
    'required' => env('APP_OAUTH_REQUIRED', 0),
    'disable_other_authentication_if_required' => env('APP_OAUTH_DISABLE_OTHER_AUTHENTICATION_IF_REQUIRED', false),
    'drivers' => json_encode([ // Store in json form to enable storing in DB
        'google' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_GOOGLE_KEY'),
            'client_secret' => env('APP_OAUTH_GOOGLE_SECRET'),
        ],
        'twitter' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_TWITTER_KEY'),
            'client_secret' => env('APP_OAUTH_TWITTER_SECRET'),
        ],
        'facebook' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_FACEBOOK_KEY'),
            'client_secret' => env('APP_OAUTH_FACEBOOK_SECRET'),
        ],
        'linkedin' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_LINKEDIN_KEY'),
            'client_secret' => env('APP_OAUTH_LINKEDIN_SECRET'),
        ],
        'github' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_GITHUB_KEY'),
            'client_secret' => env('APP_OAUTH_GITHUB_SECRET'),
        ],
        'gitlab' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_GITLAB_KEY'),
            'client_secret' => env('APP_OAUTH_GITLAB_SECRET'),
        ],
        'bitbucket' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_BITBUCKET_KEY'),
            'client_secret' => env('APP_OAUTH_BITBUCKET_SECRET'),
        ],
        'apple' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_APPLE_KEY'),
            'client_secret' => env('APP_OAUTH_APPLE_SECRET'),
            'listener' => 'SocialiteProviders\\Apple\\AppleExtendSocialite@handle',
        ],
        'microsoft' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_MICROSOFT_KEY'),
            'client_secret' => env('APP_OAUTH_MICROSOFT_SECRET'),
            'listener' => 'SocialiteProviders\\Microsoft\\MicrosoftExtendSocialite@handle',
        ],
        'discord' => [
            'enabled' => false,
            'client_id' => env('APP_OAUTH_DISCORD_KEY'),
            'client_secret' => env('APP_OAUTH_DISCORD_SECRET'),
            'listener' => 'SocialiteProviders\\Discord\\DiscordExtendSocialite@handle',
        ],
    ]),
];
