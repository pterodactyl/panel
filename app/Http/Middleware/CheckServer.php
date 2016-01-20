<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Pterodactyl\Http\Middleware;

use Closure;
use Auth;
use Pterodactyl\Models\Server;
use Debugbar;

class CheckServer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (!Auth::user()) {
            return redirect()->guest('auth/login');
        }

        $server = Server::getByUUID($request->route()->server);
        if (!$server) {
            return response()->view('errors.403', [], 403);
        }

        if ($server->installed !== 1) {
            return response()->view('errors.installing', [], 503);
        }

        return $next($request);

    }
}
