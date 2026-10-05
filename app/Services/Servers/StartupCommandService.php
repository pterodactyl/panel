<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use UnexpectedValueException;

class StartupCommandService
{
    public function __construct(private readonly EnvironmentService $environmentService) {}

    /**
     * Generates a startup command for a given server instance.
     */
    public function handle(Server $server, bool $hideAllValues = false): string
    {
        $server->loadMissing('allocation');
        $allocation = $server->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.');

        $variables = $this->environmentService->variables($server);

        $find = ['{{SERVER_MEMORY}}', '{{SERVER_IP}}', '{{SERVER_PORT}}'];
        $replace = [(string) $server->memory, $allocation->ip, (string) $allocation->port];

        foreach ($variables as $variable) {
            $find[] = '{{'.$variable->env_variable.'}}';
            $replace[] = (string) (($variable->user_viewable && ! $hideAllValues) ? ($variable->server_value ?? $variable->default_value) : '[hidden]');
        }

        return str_replace($find, $replace, $server->startup);
    }
}
