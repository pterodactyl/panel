<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;

class StartupCommandService
{
    /**
     * Generates a startup command for a given server instance.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return string
     */
    public function handle(Server $server): string
    {
        $find = ['{{SERVER_MEMORY}}', '{{SERVER_IP}}', '{{SERVER_PORT}}'];
        $replace = [$server->memory, $server->allocation->ip, $server->allocation->port];

        foreach ($server->variables as $variable) {
            $find[] = '{{' . $variable->env_variable . '}}';
            $replace[] = $variable->user_viewable ? ($variable->server_value ?? $variable->default_value) : '[hidden]';
        }

        return str_replace($find, $replace, $server->startup);
    }
}
