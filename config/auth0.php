<?php

declare(strict_types=1);

/**
 * Please review available configuration options here:
 * https://github.com/auth0/auth0-PHP#configuration-options
 */
return [
    // Should be assigned either 'api', 'management', or 'webapp' to indicate your application's use case for the SDK.
    // Determines what configuration options will be required.
    'strategy' => env('AUTH0_STRATEGY', 'webapp'),

    // Auth0 domain for your tenant, found in your Auth0 Application settings.
    'domain' => env('AUTH0_DOMAIN'),

    // If you have configured Auth0 to use a custom domain, configure it here.
    'customDomain' => env('AUTH0_CUSTOM_DOMAIN'),

    // Client ID, found in the Auth0 Application settings.
    'clientId' => env('AUTH0_CLIENT_ID'),

    // Authentication callback URI, as defined in your Auth0 Application settings.
    'redirectUri' => env('AUTH0_REDIRECT_URI', env('APP_URL') . '/callback'),

    // Client Secret, found in the Auth0 Application settings.
    'clientSecret' => env('AUTH0_CLIENT_SECRET'),

    // One or more API identifiers, found in your Auth0 API settings. The SDK uses the first value for building links. If provided, at least one of these values must match the 'aud' claim to validate an ID Token successfully.
    'audience' => \Auth0\Laravel\Configuration::stringToArrayOrNull(env('AUTH0_AUDIENCE')),

    // One or more scopes to request for Tokens. See https://auth0.com/docs/scopes
    'scope' => \Auth0\Laravel\Configuration::stringToArrayOrNull(env('AUTH0_SCOPE')),

    // One or more Organization IDs, found in your Auth0 Organization settings. The SDK uses the first value for building links. If provided, at least one of these values must match the 'org_id' claim to validate an ID Token successfully.
    'organization' => \Auth0\Laravel\Configuration::stringToArrayOrNull(env('AUTH0_ORGANIZATION')),

    // The secret used to derive an encryption key for the user identity in a session cookie and to sign the transient cookies used by the login callback.
    'cookieSecret' => env('AUTH0_COOKIE_SECRET', env('APP_KEY')),

    // How long, in seconds, before cookies expire. If set to 0 the cookie will expire at the end of the session (when the browser closes).
    'cookieExpires' => env('COOKIE_EXPIRES', 0),

    // Cookie domain, for example 'www.example.com', for use with PHP sessions and SDK cookies. Defaults to value of HTTP_HOST server environment information.
    // Note: To make cookies visible on all subdomains then the domain must be prefixed with a dot like '.example.com'.
    'cookieDomain' => env('AUTH0_COOKIE_DOMAIN'),

    // Specifies path on the domain where the cookies will work. Defaults to '/'. Use a single slash ('/') for all paths on the domain.
    'cookiePath' => env('AUTH0_COOKIE_PATH'),

    // Defaults to false. Specifies whether cookies should ONLY be sent over secure connections.
    'cookieSecure' => \Auth0\Laravel\Configuration::stringToBoolOrNull(env('AUTH0_COOKIE_SECURE'), false),

    // Named routes within your Laravel application that the SDK may call during stateful requests for redirections.
    'routes' => [
        'home' => env('AUTH0_ROUTE_HOME', '/'),
        'login' => env('AUTH0_ROUTE_LOGIN', '/auth/providers/auth0/login')
    ]
];
