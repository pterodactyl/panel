<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;

class StartupCommandService
{
    /**
     * Generates a startup command for a given server instance.
     */
    public function handle(Server $server, bool $hideAllValues = false): string
    {
        $find = ['{{SERVER_MEMORY}}', '{{SERVER_IP}}', '{{SERVER_PORT}}'];
        $replace = [$server->memory, $server->allocation->ip, $server->allocation->port];

        foreach ($server->variables as $variable) {
            $find[] = '{{' . $variable->env_variable . '}}';
            // @phpstan-ignore-next-line
            $replace[] = ($variable->user_viewable && !$hideAllValues) ? ($variable->server_value ?? $variable->default_value) : '[hidden]';
        }

        return str_replace($find, $replace, !is_null($server->startup) ? $server->startup : $server->egg->startup);
    }
}
