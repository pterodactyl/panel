<?php

namespace Pterodactyl\Services\Mounts;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Mount;
use Pterodactyl\Repositories\Eloquent\MountRepository;

class MountDeletionService
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\MountRepository
     */
    protected $repository;

    /**
     * MountDeletionService constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\MountRepository $repository
     */
    public function __construct(MountRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Delete an existing location.
     *
     * @param int|\Pterodactyl\Models\Mount $mount
     * @return int|null
     */
    public function handle($mount)
    {
        $mount = ($mount instanceof Mount) ? $mount->id : $mount;

        Assert::integerish($mount, 'First argument passed to handle must be numeric or an instance of ' . Mount::class . ', received %s.');

        return $this->repository->delete($mount);
    }
}
