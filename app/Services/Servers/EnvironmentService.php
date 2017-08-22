<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
     */
    public function process($server)
    {
        if (! $server instanceof Server) {
            if (! is_numeric($server)) {
                throw new \InvalidArgumentException(
                    'First argument passed to process() must be an instance of \\Pterodactyl\\Models\\Server or numeric.'
                );
            }

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
