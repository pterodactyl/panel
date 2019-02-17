<?php

return [

    /*
     * OAuth2 Configuration
     * More information can be found here http://oauth2-client.thephpleague.com/
     */
    'options' => [
        'clientId' => env('OAUTH2_CLIENT_ID','pterodactyl'),
        'clientSecret' => env('OAUTH2_CLIENT_SECRET'),
        'redirectUri' => env('APP_URL') .'/auth/login/oauth2/callback',
        'urlAuthorize' => env('OAUTH2_URL_AUTHORIZE','http://example.com/oauth2/authorize'),
        'urlAccessToken' => env('OAUTH2_URL_ACCESS_TOKEN','http://example.com/oauth2/token'),
        'urlResourceOwnerDetails' => env('OAUTH2_URL_RESOURCE_OWNER_DETAILS','http://example.com/oauth2/resource'),
    ],

    /*
     * Proxy Configuration
     * More Information can be found here http://oauth2-client.thephpleague.com/
     */
    'use-proxy' => env('OAUTH2_URL_PROXY_URL') != null,
    'proxy-options' => [
        'proxy' => env('OAUTH2_URL_PROXY_URL', '192.168.0.1:8888'),
        'verify' => false,
    ],

    /*
     * Fully Qualified Provider Class Name
     * https://github.com/thephpleague/oauth2-client/blob/master/docs/providers/thirdparty.md
     * Leave blank to use the default one.
     */
    'provider' => '',

    /*
     * getAuthorizationUrl() Options
     * Use this option if you need to pass options to your provider's getAuthorizationUrl() method.
     */
    'authorization-options' => [
        'scope' => preg_split( '~,~', env('OAUTH2_SCOPES', 'email')),
    ],

    /*
     * Session keys
     */
    'authorization-code-session-key' => 'oauth2_code',
    'authorization-state-session-key' => 'oauth2_state',

    /*
     * Cache the access token to avoid a new request each time
     */
    'cache-access-token' => true,
    'authorization-access-token-session-key' => 'oauth2_token',

    /*
     * Cache the resources to avoid a new request each time
     */
    'cache-resources' => true,
    'authorization-resources-session-key' => 'oauth2_resources',

];