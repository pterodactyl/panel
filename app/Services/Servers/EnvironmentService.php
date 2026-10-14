<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use InvalidArgumentException;
use Pterodactyl\Models\Server;

class EnvironmentService
{
    /** @var array<string, callable(Server): EnvironmentValue> */
    private array $additional = [];

    /**
     * Dynamically configure additional environment variables to be assigned
     * with a specific server.
     *
     * @param  callable(Server): EnvironmentValue  $closure
     */
    public function setEnvironmentKey(string $key, callable $closure): void
    {
        $this->additional[$key] = $closure;
    }

    /**
     * @return array<string, EnvironmentValue> environment variable name => value
     */
    public function forServer(Server $server): array
    {
        $server->loadMissing('location');

        $variables = [];
        foreach ($server->resolvedVariables() as $variable) {
            $variables[$variable->env_variable] = $this->environmentValue(
                $variable->env_variable,
                $variable->server_value ?? $variable->default_value,
            );
        }

        // Process environment variables defined in this file. This is done first
        // in order to allow run-time and config defined variables to take
        // priority over built-in values.
        foreach ($this->getEnvironmentMappings() as $key => $object) {
            $variables[$key] = $this->environmentValue($key, data_get($server, $object));
        }

        // Process variables set in the configuration file.
        foreach ($this->configuredEnvironmentVariables() as $key => $source) {
            $variables[$key] = $this->environmentValue(
                $key,
                is_string($source) ? data_get($server, $source) : $source($server),
            );
        }

        // Process dynamically included environment variables.
        foreach ($this->additional as $key => $closure) {
            $variables[$key] = $this->environmentValue($key, $closure($server));
        }

        return $variables;
    }

    /**
     * Return a mapping of Panel default environment variables.
     *
     * @return array<string, string> environment variable name => server attribute path
     */
    private function getEnvironmentMappings(): array
    {
        return [
            'STARTUP' => 'startup',
            'P_SERVER_LOCATION' => 'location.short',
            'P_SERVER_UUID' => 'uuid',
        ];
    }

    /** @return array<string, string|callable> */
    private function configuredEnvironmentVariables(): array
    {
        $variables = [];
        foreach (config()->array('pterodactyl.environment_variables', []) as $key => $source) {
            throw_if(! is_string($key) || (! is_string($source) && ! is_callable($source)), InvalidArgumentException::class, 'Each configured server environment variable must map a string key to an attribute path or callable.');

            $variables[$key] = $source;
        }

        return $variables;
    }

    private function environmentValue(string $key, mixed $value): bool|float|int|string|null
    {
        throw_unless($value === null || is_scalar($value), InvalidArgumentException::class, sprintf('Server environment variable "%s" must resolve to a JSON scalar.', $key));

        return $value;
    }
}
