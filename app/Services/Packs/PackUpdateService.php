<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Packs;

use App\Models\Pack;
use Illuminate\Support\Arr;
use App\Contracts\Repository\PackRepositoryInterface;
use App\Exceptions\Service\HasActiveServersException;
use App\Contracts\Repository\ServerRepositoryInterface;

class PackUpdateService
{
    /**
     * @var \App\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * PackUpdateService constructor.
     *
     * @param \App\Contracts\Repository\PackRepositoryInterface   $repository
     * @param \App\Contracts\Repository\ServerRepositoryInterface $serverRepository
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
     * @param int|\App\Models\Pack $pack
     * @param array                        $data
     * @return bool
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\HasActiveServersException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($pack, array $data)
    {
        if (! $pack instanceof Pack) {
            $pack = $this->repository->setColumns(['id', 'egg_id'])->find($pack);
        }

        if ((int) Arr::get($data, 'egg_id', $pack->egg_id) !== $pack->egg_id) {
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
