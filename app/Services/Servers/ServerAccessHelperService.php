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

use Carbon\Carbon;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\DaemonKey;
use Illuminate\Cache\Repository as CacheRepository;
use Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;
use Pterodactyl\Exceptions\Service\Server\UserNotLinkedToServerException;

class ServerAccessHelperService
{
    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface
     */
    protected $daemonKeyRepository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService
     */
    protected $daemonKeyUpdateService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * ServerAccessHelperService constructor.
     *
     * @param \Illuminate\Cache\Repository                                   $cache
     * @param \Carbon\Carbon                                                 $carbon
     * @param \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface $daemonKeyRepository
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService        $daemonKeyUpdateService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface    $repository
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface      $userRepository
     */
    public function __construct(
        CacheRepository $cache,
        Carbon $carbon,
        DaemonKeyRepositoryInterface $daemonKeyRepository,
        DaemonKeyUpdateService $daemonKeyUpdateService,
        ServerRepositoryInterface $repository,
        UserRepositoryInterface $userRepository
    ) {
        $this->cache = $cache;
        $this->carbon = $carbon;
        $this->daemonKeyRepository = $daemonKeyRepository;
        $this->daemonKeyUpdateService = $daemonKeyUpdateService;
        $this->repository = $repository;
        $this->userRepository = $userRepository;
    }

    /**
     * Return the daemon secret to use when making a connection.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param int|\Pterodactyl\Models\User   $user
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Server\UserNotLinkedToServerException
     * @throws \RuntimeException
     */
    public function handle($server, $user)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        if (! $user instanceof User) {
            $user = $this->userRepository->find($user);
        }

        $keys = $server->relationLoaded('keys') ? $server->keys : $this->daemonKeyRepository->getServerKeys($server->id);
        $key = $keys->where('user_id', $user->root_admin ? $server->owner_id : $user->id)->first();

        if (is_null($key)) {
            throw new UserNotLinkedToServerException;
        }

        if (max($this->carbon->now()->diffInSeconds($key->expires_at, false), 0) === 0) {
            $key = $this->daemonKeyUpdateService->handle($key);
        }

        return ($key instanceof DaemonKey) ? $key->secret : $key;
    }
}
