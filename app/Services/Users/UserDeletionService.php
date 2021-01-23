<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

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
     * @param int|\Pterodactyl\Models\User $user
     *
     * @return bool|null
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function handle($user)
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        $servers = $this->serverRepository->setColumns('id')->findCountWhere([['owner_id', '=', $user]]);
        if ($servers > 0) {
            throw new DisplayException($this->translator->trans('admin/user.exceptions.user_has_servers'));
        }

        return $this->repository->delete($user);
    }
}
