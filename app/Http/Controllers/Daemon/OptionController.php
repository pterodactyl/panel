<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Controllers\Daemon;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Controller;

class OptionController extends Controller
{
    public function details(Request $request, $server)
    {
        $server = Server::with('allocation', 'option', 'variables.variable')->where('uuid', $server)->firstOrFail();

        $environment = $server->variables->map(function ($item) {
            return sprintf('%s=%s', $item->variable->env_variable, $item->variable_value);
        });

        return response()->json([
            'scripts' => [
                'install' => (! $server->option->script_install) ? null : str_replace(["\r\n", "\n", "\r"], "\n", $server->option->script_install),
                'privileged' => $server->option->script_is_privileged,
            ],
            'config' => [
                'container' => $server->option->script_container,
                'entry' => $server->option->script_entry,
            ],
            'env' => $environment->merge([
                'STARTUP=' . $server->startup,
                'SERVER_MEMORY=' . $server->memory,
                'SERVER_IP=' . $server->allocation->ip,
                'SERVER_PORT=' . $server->allocation->port,
            ])->toArray(),
        ]);
    }
}
