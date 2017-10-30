<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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

        $mergeInto = [
            'STARTUP=' . $server->startup,
            'SERVER_MEMORY=' . $server->memory,
            'SERVER_IP=' . $server->allocation->ip,
            'SERVER_PORT=' . $server->allocation->port,
        ];

        if ($environment->count() === 0) {
            $environment = collect($mergeInto);
        }

        return response()->json([
            'scripts' => [
                'install' => (! $server->option->copy_script_install) ? null : str_replace(["\r\n", "\n", "\r"], "\n", $server->option->copy_script_install),
                'privileged' => $server->option->script_is_privileged,
            ],
            'config' => [
                'container' => $server->option->copy_script_container,
                'entry' => $server->option->copy_script_entry,
            ],
            'env' => $environment->toArray(),
        ]);
    }
}
