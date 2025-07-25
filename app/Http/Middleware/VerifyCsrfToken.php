<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification. These are
     * never hit by the front-end, and require specific token validation
     * to work.
     */
    protected $except = ['remote/*', 'daemon/*'];
}
