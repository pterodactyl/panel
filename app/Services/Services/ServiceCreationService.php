<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Service;
use Pterodactyl\Traits\Services\CreatesServiceIndex;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class ServiceCreationService
{
    use CreatesServiceIndex;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $repository;

    /**
     * ServiceCreationService constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                      $config
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface $repository
     */
    public function __construct(
        ConfigRepository $config,
        ServiceRepositoryInterface $repository
    ) {
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Create a new service on the system.
     *
     * @param array $data
     * @return \Pterodactyl\Models\Service
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data): Service
    {
        return $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'author' => $this->config->get('pterodactyl.service.author'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description'),
            'startup' => array_get($data, 'startup'),
            'index_file' => $this->getIndexScript(),
        ], true, true);
    }
}
