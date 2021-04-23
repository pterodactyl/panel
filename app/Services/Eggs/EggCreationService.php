<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Eggs;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Egg;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Exceptions\Service\Egg\NoParentConfigurationFoundException;

// When a mommy and a daddy pterodactyl really like each other...
class EggCreationService
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggCreationService constructor.
     */
    public function __construct(ConfigRepository $config, EggRepositoryInterface $repository)
    {
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Create a new service option and assign it to the given service.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Egg\NoParentConfigurationFoundException
     */
    public function handle(array $data): Egg
    {
        $data['config_from'] = array_get($data, 'config_from');
        if (!is_null($data['config_from'])) {
            $results = $this->repository->findCountWhere([
                ['nest_id', '=', array_get($data, 'nest_id')],
                ['id', '=', array_get($data, 'config_from')],
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
