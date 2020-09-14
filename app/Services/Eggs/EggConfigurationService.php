<?php

namespace Pterodactyl\Services\Eggs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pterodactyl\Models\Server;
use Symfony\Component\Yaml\Yaml;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class EggConfigurationService
{
    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    private $configurationStructureService;

    /**
     * EggConfigurationService constructor.
     *
     * @param \Pterodactyl\Services\Servers\ServerConfigurationStructureService $configurationStructureService
     */
    public function __construct(ServerConfigurationStructureService $configurationStructureService)
    {
        $this->configurationStructureService = $configurationStructureService;
    }

    /**
     * Return an Egg file to be used by the Daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function handle(Server $server): array
    {
        $configs = $this->replacePlaceholders(
            $server, Yaml::parse($server->egg->inherit_config_files, Yaml::PARSE_OBJECT_FOR_MAP)
        );

        return [
            'startup' => $this->convertStartupToNewFormat(Yaml::parse($server->egg->inherit_config_startup)),
            'stop' => $this->convertStopToNewFormat($server->egg->inherit_config_stop),
            'configs' => $configs,
        ];
    }

    /**
     * Convert the "done" variable into an array if it is not currently one.
     *
     * @param array $startup
     * @return array
     */
    protected function convertStartupToNewFormat(array $startup)
    {
        $done = Arr::get($startup, 'done');

        return [
            'done' => is_string($done) ? [$done] : $done,
            'user_interaction' => Arr::get($startup, 'userInteraction') ?? Arr::get($startup, 'user_interaction') ?? [],
            'strip_ansi' => Arr::get($startup, 'strip_ansi') ?? false,
        ];
    }

    /**
     * Converts a legacy stop string into a new generation stop option for a server.
     *
     * For most eggs, this ends up just being a command sent to the server console, but
     * if the stop command is something starting with a caret (^), it will be converted
     * into the associated kill signal for the instance.
     *
     * @param string $stop
     * @return array
     */
    protected function convertStopToNewFormat(string $stop): array
    {
        if (! Str::startsWith($stop, '^')) {
            return [
                'type' => 'command',
                'value' => $stop,
            ];
        }

        $signal = substr($stop, 1);
        if (strtoupper($signal) === 'C') {
            return [
                'type' => 'stop',
                'value' => null,
            ];
        }

        return [
            'type' => 'signal',
            'value' => strtoupper($signal),
        ];
    }

    /**
     * @param \Pterodactyl\Models\Server $server
     * @param object $configs
     * @return array
     */
    protected function replacePlaceholders(Server $server, object $configs)
    {
        // Get the legacy configuration structure for the server so that we
        // can property map the egg placeholders to values.
        $structure = $this->configurationStructureService->handle($server, [], true);

        $response = [];
        // Normalize the output of the configuration for the new Wings Daemon to more
        // easily ingest, as well as make things more flexible down the road.
        foreach ($configs as $file => $data) {
            $append = array_merge((array)$data, ['file' => $file, 'replace' => []]);

            foreach ($this->iterate($data->find, $structure) as $find => $replace) {
                if (is_object($replace)) {
                    foreach ($replace as $match => $replaceWith) {
                        $append['replace'][] = [
                            'match' => $find,
                            'if_value' => $match,
                            'replace_with' => $replaceWith,
                        ];
                    }

                    continue;
                }

                $append['replace'][] = [
                    'match' => $find,
                    'replace_with' => $replace,
                ];
            }

            unset($append['find']);

            $response[] = $append;
        }

        return $response;
    }

    /**
     * Replaces the legacy modifies from eggs with their new counterpart. The legacy Daemon would
     * set SERVER_MEMORY, SERVER_IP, and SERVER_PORT with their respective values on the Daemon
     * side. Ensure that anything referencing those properly replaces them with the matching config
     * value.
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function replaceLegacyModifiers(string $key, string $value): string
    {
        switch ($key) {
            case 'config.docker.interface':
                $replace = 'config.docker.network.interface';
                break;
            case 'server.build.env.SERVER_MEMORY':
            case 'env.SERVER_MEMORY':
                $replace = 'server.build.memory';
                break;
            case 'server.build.env.SERVER_IP':
            case 'env.SERVER_IP':
                $replace = 'server.build.default.ip';
                break;
            case 'server.build.env.SERVER_PORT':
            case 'env.SERVER_PORT':
                $replace = 'server.build.default.port';
                break;
            // By default we don't need to change anything, only if we ended up matching a specific legacy item.
            default:
                $replace = $key;
        }

        return str_replace("{{{$key}}}", "{{{$replace}}}", $value);
    }

    /**
     * @param mixed $value
     * @param array $structure
     * @return mixed|null
     */
    protected function matchAndReplaceKeys($value, array $structure)
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

            // Don't do a replacement on anything that is not a string, we don't want to unintentionally
            // modify the resulting output.
            if (! is_string($value)) {
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
                $plucked = Arr::get($structure, preg_replace('/^server\./', '', $key), '');

                $value = str_replace("{{{$key}}}", $plucked, $value);
                continue;
            }

            // Finally, replace anything starting with env. with the expected environment
            // variable from the server configuration.
            $plucked = Arr::get(
                $structure, preg_replace('/^env\./', 'build.env.', $key), ''
            );

            $value = str_replace("{{{$key}}}", $plucked, $value);
        }

        return $value;
    }

    /**
     * Iterates over a set of "find" values for a given file in the parser configuration. If
     * the value of the line match is something iterable, continue iterating, otherwise perform
     * a match & replace.
     *
     * @param mixed $data
     * @param array $structure
     * @return mixed
     */
    private function iterate($data, array $structure)
    {
        if (! is_iterable($data) && ! is_object($data)) {
            return $data;
        }

        // Remember, in PHP objects are always passed by reference, so if we do not clone this object
        // instance we'll end up making modifications to the object outside the scope of this function
        // which leads to some fun behavior in the parser.
        $clone = clone $data;
        foreach ($clone as $key => &$value) {
            if (is_iterable($value) || is_object($value)) {
                $value = $this->iterate($value, $structure);

                continue;
            }

            $value = $this->matchAndReplaceKeys($value, $structure);
        }

        return $clone;
    }
}
