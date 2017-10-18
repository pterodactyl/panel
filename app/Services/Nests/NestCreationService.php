<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Nests;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Nest;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class NestCreationService
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * NestCreationService constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \Pterodactyl\Contracts\Repository\NestRepositoryInterface $repository
     */
    public function __construct(ConfigRepository $config, NestRepositoryInterface $repository)
    {
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Create a new nest on the system.
     *
     * @param array $data
     * @return \Pterodactyl\Models\Nest
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data): Nest
    {
        return $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'author' => $this->config->get('pterodactyl.service.author'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description'),
        ], true, true);
    }
}
