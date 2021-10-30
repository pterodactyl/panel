<?php

namespace Pterodactyl\Services\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class UserDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * DeletionService constructor.
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        Translator $translator,
        UserRepositoryInterface $repository
    ) {
        $this->repository = $repository;
        $this->translator = $translator;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Delete a user from the panel only if they have no servers attached to their account.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function handle(User $user): void
    {
        $servers = $this->serverRepository->setColumns('id')->findCountWhere([['owner_id', '=', $user->id]]);
        if ($servers > 0) {
            throw new DisplayException($this->translator->get('admin/user.exceptions.user_has_servers'));
        }

        $this->repository->delete($user->id);
    }
}
