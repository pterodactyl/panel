<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $serverRepository
     * @param \Illuminate\Contracts\Translation\Translator                $translator
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface   $repository
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
     * @return bool|null
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function handle($user)
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        $servers = $this->serverRepository->withColumns('id')->findCountWhere([['owner_id', '=', $user]]);
        if ($servers > 0) {
            throw new DisplayException($this->translator->trans('admin/user.exceptions.user_has_servers'));
        }

        return $this->repository->delete($user);
    }
}
