<?php

namespace Pterodactyl\Services\Subusers;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class SubuserDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * SubuserDeletionService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface $repository
     */
    public function __construct(
        SubuserRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Delete a subuser and their associated permissions from the Panel and Daemon.
     *
     * @param \Pterodactyl\Models\Subuser $subuser
     */
    public function handle(Subuser $subuser)
    {
        $this->repository->delete($subuser->id);
    }
}
