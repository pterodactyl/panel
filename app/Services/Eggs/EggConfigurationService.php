<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Eggs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class EggConfigurationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    private $configurationStructureService;

    /**
     * EggConfigurationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface $repository
     * @param \Pterodactyl\Services\Servers\ServerConfigurationStructureService $configurationStructureService
     */
    public function __construct(
        EggRepositoryInterface $repository,
        ServerConfigurationStructureService $configurationStructureService
    ) {
        $this->repository = $repository;
        $this->configurationStructureService = $configurationStructureService;
    }

    /**
     * Return an Egg file to be used by the Daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Server $server): array
    {
        $configs = $this->replacePlaceholders(
            $server, json_decode($server->egg->inherit_config_files)
        );

        return [
            'startup' => json_decode($server->egg->inherit_config_startup),
            'stop' => $this->convertStopToNewFormat($server->egg->inherit_config_stop),
            'configs' => $configs,
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
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    protected function replacePlaceholders(Server $server, object $configs)
    {
        // Get the legacy configuration structure for the server so that we
        // can property map the egg placeholders to values.
        $structure = $this->configurationStructureService->handle($server, true);

        foreach ($configs as $file => $data) {
            foreach ($data->find ?? [] as &$value) {
                preg_match('/^{{(?<key>.*)}}$/', $value, $matches);

                if (! $key = $matches['key'] ?? null) {
                    continue;
                }

                // Matched something in {{server.X}} format, now replace that with the actual
                // value from the server properties.
                //
                // The Daemon supports server.X, env.X, and config.X placeholders.
                if (! Str::startsWith($key, ['server.', 'env.', 'config.'])) {
                    continue;
                }

                // We don't want to do anything with config keys since the Daemon will need to handle
                // that. For example, the Spigot egg uses "config.docker.interface" to identify the Docker
                // interface to proxy through, but the Panel would be unaware of that.
                if (Str::startsWith($key, 'config.')) {
                    $value = "{{{$key}}}";
                    continue;
                }

                // The legacy Daemon would set SERVER_MEMORY, SERVER_IP, and SERVER_PORT with their
                // respective values on the Daemon side. Ensure that anything referencing those properly
                // replaces them with the matching config value.
                switch ($key) {
                    case 'server.build.env.SERVER_MEMORY':
                    case 'env.SERVER_MEMORY':
                        $key = 'server.build.memory';
                        break;
                    case 'server.build.env.SERVER_IP':
                    case 'env.SERVER_IP':
                        $key = 'server.build.default.ip';
                        break;
                    case 'server.build.env.SERVER_PORT':
                    case 'env.SERVER_PORT':
                        $key = 'server.build.default.port';
                        break;
                }

                // Replace anything starting with "server." with the value out of the server configuration
                // array that used to be created for the old daemon.
                if (Str::startsWith($key, 'server.')) {
                    $value = Arr::get(
                        $structure, preg_replace('/^server\./', '', $key), ''
                    );
                    continue;
                }

                // Finally, replace anything starting with env. with the expected environment
                // variable from the server configuration.
                $value = Arr::get(
                    $structure, preg_replace('/^env\./', 'build.env.', $key), ''
                );
            }
        }

        $response = [];
        // Normalize the output of the configuration for the new Wings Daemon to more
        // easily ingest, as well as make things more flexible down the road.
        foreach ($configs as $file => $data) {
            $append = ['file' => $file, 'replace' => []];

            // I like to think I understand PHP pretty well, but if you don't pass $value
            // by reference here, you'll end up with a resursive array loop if the config
            // file has two replacements that reference the same item in the configuration
            // array for the server.
            foreach ($data as $key => &$value) {
                if ($key !== 'find') {
                    $append[$key] = $value;
                    continue;
                }

                foreach ($value as $find => $replace) {
                    $append['replace'][] = ['match' => $find, 'value' => $replace];
                }
            }

            $response[] = $append;
        }

        return $response;
    }
}
