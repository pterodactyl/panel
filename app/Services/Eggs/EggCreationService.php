<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Eggs;

use App\Models\Egg;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use App\Contracts\Repository\EggRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Exceptions\Service\Egg\NoParentConfigurationFoundException;

// When a mommy and a daddy pterodactyl really like each other...
class EggCreationService
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggCreationService constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                  $config
     * @param \App\Contracts\Repository\EggRepositoryInterface $repository
     */
    public function __construct(ConfigRepository $config, EggRepositoryInterface $repository)
    {
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Create a new service option and assign it to the given service.
     *
     * @param array $data
     * @return \App\Models\Egg
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Egg\NoParentConfigurationFoundException
     */
    public function handle(array $data): Egg
    {
        $data['config_from'] = Arr::get($data, 'config_from');
        if (! is_null($data['config_from'])) {
            $results = $this->repository->findCountWhere([
                ['nest_id', '=', Arr::get($data, 'nest_id')],
                ['id', '=', Arr::get($data, 'config_from')],
            ]);

            if ($results !== 1) {
                throw new NoParentConfigurationFoundException(trans('exceptions.nest.egg.must_be_child'));
            }
        }

        return $this->repository->create(array_merge($data, [
            'uuid' => Uuid::uuid4()->toString(),
            'author' => $this->config->get('pterodactyl.service.author'),
        ]), true, true);
    }
}
