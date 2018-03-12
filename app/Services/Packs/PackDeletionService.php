<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Packs;

use Pterodactyl\Models\Pack;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class PackDeletionService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $storage;

    /**
     * PackDeletionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                    $connection
     * @param \Illuminate\Contracts\Filesystem\Factory                    $storage
     * @param \Pterodactyl\Contracts\Repository\PackRepositoryInterface   $repository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $serverRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        FilesystemFactory $storage,
        PackRepositoryInterface $repository,
        ServerRepositoryInterface $serverRepository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
        $this->storage = $storage;
    }

    /**
     * Delete a pack from the database as well as the archive stored on the server.
     *
     * @param  int|\Pterodactyl\Models\Pack$pack
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($pack)
    {
        if (! $pack instanceof Pack) {
            $pack = $this->repository->setColumns(['id', 'uuid'])->find($pack);
        }

        $count = $this->serverRepository->findCountWhere([['pack_id', '=', $pack->id]]);
        if ($count !== 0) {
            throw new HasActiveServersException(trans('exceptions.packs.delete_has_servers'));
        }

        $this->connection->beginTransaction();
        $this->repository->delete($pack->id);
        $this->storage->disk()->deleteDirectory('packs/' . $pack->uuid);
        $this->connection->commit();
    }
}
