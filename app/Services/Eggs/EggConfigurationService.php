<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Eggs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonException;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;
use Pterodactyl\Support\JsonEmptyObject;
use Stringable;
use UnexpectedValueException;

class EggConfigurationService
{
    /**
     * EggConfigurationService constructor.
     */
    public function __construct(private readonly ServerConfigurationStructureService $configurationStructureService) {}

    /**
     * Return an Egg file to be used by the Daemon.
     *
     * @return array{
     *     startup: array{done: mixed, user_interaction: array{}, strip_ansi: mixed},
     *     stop: array{type: string, value: string},
     *     configs: EggConfigurationOutputs,
     * }
     */
    public function handle(Server $server): array
    {
        $egg = $server->egg ?? throw new UnexpectedValueException('The server does not have an egg assigned.');
        $configs = $this->replacePlaceholders($server, $this->configurationFiles($egg->inherit_config_files));

        return [
            'startup' => $this->convertStartupToNewFormat($this->jsonObject($egg->inherit_config_startup)),
            'stop' => $this->convertStopToNewFormat($egg->inherit_config_stop ?? ''),
            'configs' => $configs,
        ];
    }

    /**
     * Convert the "done" variable into an array if it is not currently one.
     *
     * @param  array<string, mixed>  $startup
     * @return array{done: mixed, user_interaction: array{}, strip_ansi: mixed}
     */
    protected function convertStartupToNewFormat(array $startup): array
    {
        $done = $startup['done'] ?? null;

        return [
            'done' => is_string($done) ? [$done] : $done,
            'user_interaction' => [],
            'strip_ansi' => $startup['strip_ansi'] ?? false,
        ];
    }

    /**
     * Converts a legacy stop string into a new generation stop option for a server.
     *
     * For most eggs, this ends up just being a command sent to the server console, but
     * if the stop command is something starting with a caret (^), it will be converted
     * into the associated kill signal for the instance.
     *
     * @return array{type: string, value: string}
     */
    protected function convertStopToNewFormat(string $stop): array
    {
        if (! Str::startsWith($stop, '^')) {
            return [
                'type' => 'command',
                'value' => $stop,
            ];
        }

        $signal = mb_substr($stop, 1);

        return [
            'type' => 'signal',
            'value' => mb_strtoupper($signal),
        ];
    }

    /**
     * @param  EggConfigurationFiles  $configs  the decoded "config.files" JSON structure for the egg
     * @return EggConfigurationOutputs
     */
    protected function replacePlaceholders(Server $server, array $configs): array
    {
        // Get the legacy configuration structure for the server so that we
        // can property map the egg placeholders to values.
        $structure = $this->configurationStructureService->legacyForServer($server);

        $response = [];
        // Normalize the output of the configuration for the new Wings Daemon to more
        // easily ingest, as well as make things more flexible down the road.
        foreach ($configs as $file => $data) {
            $replaceValues = [];

            foreach ($this->iterate($data['find'], $structure) as $find => $replace) {
                if (is_array($replace)) {
                    foreach ($replace as $match => $replaceWith) {
                        $replaceValues[] = [
                            'match' => $find,
                            'if_value' => $match,
                            'replace_with' => $replaceWith,
                        ];
                    }

                    continue;
                }

                $replaceValues[] = [
                    'match' => $find,
                    'replace_with' => $replace,
                ];
            }

            unset($data['find']);

            $response[] = array_merge($data, [
                'file' => $file,
                'replace' => $replaceValues,
            ]);
        }

        return $response;
    }

    /**
     * Replaces the legacy modifies from eggs with their new counterpart. The legacy Daemon would
     * set SERVER_MEMORY, SERVER_IP, and SERVER_PORT with their respective values on the Daemon
     * side. Ensure that anything referencing those properly replaces them with the matching config
     * value.
     */
    protected function replaceLegacyModifiers(string $key, string $value): string
    {
        $replace = match ($key) {
            'config.docker.interface' => 'config.docker.network.interface',
            'server.build.env.SERVER_MEMORY', 'env.SERVER_MEMORY' => 'server.build.memory',
            'server.build.env.SERVER_IP', 'env.SERVER_IP' => 'server.build.default.ip',
            'server.build.env.SERVER_PORT', 'env.SERVER_PORT' => 'server.build.default.port',
            // By default, we don't need to change anything, only if we ended up matching a specific legacy item.
            default => $key,
        };

        return str_replace("{{{$key}}}", "{{{$replace}}}", $value);
    }

    /**
     * @param  array<string, mixed>  $structure  the legacy server configuration structure
     */
    protected function matchAndReplaceKeys(string $value, array $structure): string
    {
        preg_match_all('/{{(?<key>[\w.-]*)}}/', $value, $matches);

        foreach ($matches['key'] as $key) {
            // Matched something in {{server.X}} format, now replace that with the actual
            // value from the server properties.
            //
            // The Daemon supports server.X, env.X, and config.X placeholders.
            if (! Str::startsWith($key, ['server.', 'env.', 'config.'])) {
                continue;
            }

            $value = $this->replaceLegacyModifiers($key, $value);

            // We don't want to do anything with config keys since the Daemon will need to handle
            // that. For example, the Spigot egg uses "config.docker.interface" to identify the Docker
            // interface to proxy through, but the Panel would be unaware of that.
            if (Str::startsWith($key, 'config.')) {
                continue;
            }

            // Replace anything starting with "server." with the value out of the server configuration
            // array that used to be created for the old daemon.
            if (Str::startsWith($key, 'server.')) {
                $plucked = Arr::get($structure, Str::after($key, 'server.'), '');
                $value = str_replace("{{{$key}}}", $this->placeholderValue($plucked), $value);

                continue;
            }

            // Finally, replace anything starting with env. with the expected environment
            // variable from the server configuration.
            $plucked = Arr::get(
                $structure,
                'build.env.'.Str::after($key, 'env.'),
                ''
            );

            $value = str_replace("{{{$key}}}", $this->placeholderValue($plucked), $value);
        }

        return $value;
    }

    /**
     * Replaces the placeholders in every string of a file's "find" values, at any depth.
     *
     * @param  array<mixed>  $data
     * @param  array<string, mixed>  $structure  the legacy server configuration structure
     * @return array<mixed>
     */
    private function iterate(array $data, array $structure): array
    {
        array_walk_recursive($data, function (mixed &$value) use ($structure): void {
            if (is_string($value)) {
                $value = $this->matchAndReplaceKeys($value, $structure);
            }
        });

        return $data;
    }

    /** @return EggConfigurationFiles */
    private function configurationFiles(?string $encoded): array
    {
        $files = [];
        foreach ($this->jsonObject($encoded) as $file => $value) {
            if (! is_array($value)) {
                continue;
            }

            $configuration = [];
            foreach ($value as $key => $entry) {
                if (is_string($key)) {
                    $configuration[$key] = $entry;
                }
            }

            if (! isset($configuration['find']) || ! is_array($configuration['find'])) {
                continue;
            }

            $files[$file] = $configuration;
        }

        return $files;
    }

    /**
     * Decodes one of an egg's JSON config blocks. Wings receives every server on a node in
     * one list, so a block that is empty or not JSON is reported and read as empty rather
     * than failing the list for every other server.
     *
     * @return array<string, mixed>
     */
    private function jsonObject(?string $encoded): array
    {
        if ($encoded === null || mb_trim($encoded) === '') {
            return [];
        }

        try {
            $value = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            report($jsonException);

            return [];
        }

        if (! is_array($value)) {
            return [];
        }

        $object = [];
        foreach ($value as $key => $entry) {
            if (is_string($key)) {
                $object[$key] = $entry;
            }
        }

        return $object;
    }

    /**
     * The value as PHP's string conversion would give it, with arrays and objects shown as "Array".
     */
    private function placeholderValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '',
            is_array($value), $value instanceof JsonEmptyObject => 'Array',
            is_scalar($value), $value instanceof Stringable => (string) $value,
            default => '',
        };
    }
}
