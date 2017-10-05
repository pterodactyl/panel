<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class EnvironmentService
{
    const ENVIRONMENT_CASTS = [
        'STARTUP' => 'startup',
        'P_SERVER_LOCATION' => 'location.short',
        'P_SERVER_UUID' => 'uuid',
    ];

    /**
     * @var array
     */
    protected $additional = [];

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * EnvironmentService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Dynamically configure additional environment variables to be assigned
     * with a specific server.
     *
     * @param string   $key
     * @param callable $closure
     * @return $this
     */
    public function setEnvironmentKey($key, callable $closure)
    {
        $this->additional[] = [$key, $closure];

        return $this;
    }

    /**
     * Take all of the environment variables configured for this server and return
     * them in an easy to process format.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function process($server)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        $variables = $this->repository->getVariablesWithValues($server->id);

        // Process static environment variables defined in this file.
        foreach (self::ENVIRONMENT_CASTS as $key => $object) {
            $variables[$key] = object_get($server, $object);
        }

        // Process dynamically included environment variables.
        foreach ($this->additional as $item) {
            $variables[$item[0]] = call_user_func($item[1], $server);
        }

        return $variables;
    }
}
