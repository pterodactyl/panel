<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Users;

use App\Models\User;
use App\Exceptions\DisplayException;
use Illuminate\Contracts\Translation\Translator;
use App\Contracts\Repository\UserRepositoryInterface;
use App\Contracts\Repository\ServerRepositoryInterface;

class UserDeletionService
{
    /**
     * @var \App\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * DeletionService constructor.
     *
     * @param \App\Contracts\Repository\ServerRepositoryInterface $serverRepository
     * @param \Illuminate\Contracts\Translation\Translator                $translator
     * @param \App\Contracts\Repository\UserRepositoryInterface   $repository
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
     * @param int|\App\Models\User $user
     * @return bool|null
     *
     * @throws \App\Exceptions\DisplayException
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
