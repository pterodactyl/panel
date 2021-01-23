<?php

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
    private $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    private $repository;

    /**
     * NestCreationService constructor.
     */
    public function __construct(ConfigRepository $config, NestRepositoryInterface $repository)
    {
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Create a new nest on the system.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data, string $author = null): Nest
    {
        return $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'author' => $author ?? $this->config->get('pterodactyl.service.author'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description'),
        ], true, true);
    }
}
