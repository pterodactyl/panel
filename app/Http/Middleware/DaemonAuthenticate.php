<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Middleware;

use Closure;
use Pterodactyl\Models\Node;
use Illuminate\Contracts\Auth\Guard;

class DaemonAuthenticate
{
    /**
     * The Guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\Guard
     */
    protected $auth;

    /**
     * An array of route names to not apply this middleware to.
     *
     * @var array
     */
    protected $except = [
        'daemon.configuration',
    ];

    /**
     * Create a new filter instance.
     *
     * @param \Illuminate\Contracts\Auth\Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        if (! $request->header('X-Access-Node')) {
            return abort(403);
        }

        $node = Node::where('daemonSecret', $request->header('X-Access-Node'))->first();
        if (! $node) {
            return abort(401);
        }

        return $next($request);
    }
}
