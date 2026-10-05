<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;

class EnvironmentService
{
    /** @var array<string, callable(Server): ApiScalar> */
    private array $additional = [];

    /**
     * Dynamically configure additional environment variables to be assigned
     * with a specific server.
     */
    /** @param callable(Server): ApiScalar $closure */
    public function setEnvironmentKey(string $key, callable $closure): void
    {
        $this->additional[$key] = $closure;
    }

    /**
     * Return the dynamically added additional keys.
     *
     * @return array<string, callable(Server): ApiScalar>
     */
    public function getEnvironmentKeys(): array
    {
        return $this->additional;
    }

    /**
     * Take all of the environment variables configured for this server and return
     * them in an easy to process format.
     *
     * @return array<string, ApiScalar> environment variable name => value
     */
    public function handle(Server $server): array
    {
        $server->loadMissing('location');

        $variables = [];
        foreach ($this->variables($server) as $variable) {
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
     * Resolve an egg's variables with the values overridden for this server. When
     * the list endpoint has eagerly loaded both relationships this avoids issuing
     * a query for every transformed server.
     *
     * @return Collection<int, EggVariable>
     */
    public function variables(Server $server): Collection
    {
        if (! $server->relationLoaded('egg')
            || ! $server->egg?->relationLoaded('variables')
            || ! $server->relationLoaded('serverVariables')
        ) {
            return $server->variables()->get();
        }

        $overrides = $server->serverVariables->pluck('variable_value', 'variable_id');

        return $server->egg->variables->map(function (EggVariable $variable) use ($overrides): EggVariable {
            $resolved = clone $variable;
            $resolved->setAttribute('server_value', $overrides->get($variable->id));

            return $resolved;
        });
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
        $configured = config('pterodactyl.environment_variables', []);
        throw_unless(is_array($configured), InvalidArgumentException::class, 'Configured server environment variables must be an array.');

        $variables = [];
        foreach ($configured as $key => $source) {
            throw_if(! is_string($key) || (! is_string($source) && ! is_callable($source)), InvalidArgumentException::class, 'Each configured server environment variable must map a string key to an attribute path or callable.');

            $variables[$key] = $source;
        }

        return $variables;
    }

    /**
     * @phpstan-assert ApiScalar $value
     *
     * @return ApiScalar
     */
    private function environmentValue(string $key, mixed $value): bool|float|int|string|null
    {
        if ($value === null || is_bool($value) || is_float($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException(sprintf('Server environment variable "%s" must resolve to a JSON scalar.', $key));
    }
}
