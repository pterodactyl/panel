<?php

namespace Pterodactyl\Services\Mounts;

use Pterodactyl\Models\Mount;
use Pterodactyl\Repositories\Eloquent\MountRepository;

class MountUpdateService
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\MountRepository
     */
    protected $repository;

    /**
     * MountUpdateService constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\MountRepository $repository
     */
    public function __construct(MountRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update an existing location.
     *
     * @param int|\Pterodactyl\Models\Mount $mount
     * @param array $data
     * @return \Pterodactyl\Models\Mount
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($mount, array $data)
    {
        $mount = ($mount instanceof Mount) ? $mount->id : $mount;

        return $this->repository->update($mount, $data);
    }
}
