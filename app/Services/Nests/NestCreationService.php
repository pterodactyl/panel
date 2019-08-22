<?php

namespace App\Services\Nests;

use App\Models\Nest;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use App\Contracts\Repository\NestRepositoryInterface;

class NestCreationService
{
    /**
     * @var \App\Contracts\Repository\NestRepositoryInterface
     */
    private $repository;

    /**
     * NestCreationService constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \App\Contracts\Repository\NestRepositoryInterface $repository
     */
    public function __construct(NestRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new nest on the system.
     *
     * @param array       $data
     * @param string|null $author
     * @return \App\Models\Nest
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function handle(array $data, string $author = null): Nest
    {
        return $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'author' => $author ?? config('pterodactyl.service.author'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description'),
        ], true, true);
    }
}
