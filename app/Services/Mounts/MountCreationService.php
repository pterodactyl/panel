<?php

namespace Pterodactyl\Services\Mounts;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Repositories\Eloquent\MountRepository;

class MountCreationService
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\MountRepository
     */
    protected $repository;

    /**
     * MountCreationService constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\MountRepository $repository
     */
    public function __construct(MountRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new mount.
     *
     * @param array $data
     * @return \Pterodactyl\Models\Mount
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        return $this->repository->create(array_merge($data, [
            'uuid' => Uuid::uuid4()->toString(),
        ]), true, true);
    }
}
