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
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class PackUpdateService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * PackUpdateService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\PackRepositoryInterface   $repository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $serverRepository
     */
    public function __construct(
        PackRepositoryInterface $repository,
        ServerRepositoryInterface $serverRepository
    ) {
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Update a pack.
     *
     * @param int|\Pterodactyl\Models\Pack $pack
     * @param array                        $data
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($pack, array $data)
    {
        if (! $pack instanceof Pack) {
            $pack = $this->repository->setColumns(['id', 'egg_id'])->find($pack);
        }

        if ((int) array_get($data, 'egg_id', $pack->egg_id) !== $pack->egg_id) {
            $count = $this->serverRepository->findCountWhere([['pack_id', '=', $pack->id]]);

            if ($count !== 0) {
                throw new HasActiveServersException(trans('exceptions.packs.update_has_servers'));
            }
        }

        // Transform values to boolean
        $data['selectable'] = isset($data['selectable']);
        $data['visible'] = isset($data['visible']);
        $data['locked'] = isset($data['locked']);

        return $this->repository->withoutFreshModel()->update($pack->id, $data);
    }
}
