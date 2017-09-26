<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Exceptions\Service\Server\UserNotLinkedToServerException;

class ServerAccessHelperService
{
    public function __construct(
        CacheRepository $cache,
        ServerRepositoryInterface $repository,
        SubuserRepositoryInterface $subuserRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->cache = $cache;
        $this->repository = $repository;
        $this->subuserRepository = $subuserRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @param int|\Pterodactyl\Models\Server $server
     * @param int|\Pterodactyl\Models\User   $user
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Server\UserNotLinkedToServerException
     */
    public function handle($server, $user)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        if (! $user instanceof User) {
            $user = $this->userRepository->find($user);
        }

        if ($user->root_admin || $server->owner_id === $user->id) {
            return $server->daemonSecret;
        }

        if (! in_array($server->id, $this->repository->getUserAccessServers($user->id))) {
            throw new UserNotLinkedToServerException;
        }

        $subuser = $this->subuserRepository->withColumns('daemonSecret')->findWhere([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ]);

        return $subuser->daemonSecret;
    }
}
