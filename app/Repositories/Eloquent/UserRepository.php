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

namespace Pterodactyl\Repositories\Eloquent;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Models\User;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var bool|array
     */
    protected $searchTerm = false;

    /**
     * UserRepository constructor.
     *
     * @param \Illuminate\Foundation\Application      $application
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Application $application, ConfigRepository $config)
    {
        parent::__construct($application);

        $this->config = $config;
    }

    public function model()
    {
        return User::class;
    }

    public function search($term)
    {
        if (empty($term)) {
            return $this;
        }

        $clone = clone $this;
        $clone->searchTerm = $term;

        return $clone;
    }

    public function getAllUsersWithCounts()
    {
        $users = $this->getBuilder()->withCount('servers', 'subuserOf');

        if ($this->searchTerm) {
            $users->search($this->searchTerm);
        }

        return $users->paginate(
            $this->config->get('pterodactyl.paginate.admin.users'), $this->getColumns()
        );
    }

    /**
     * Delete a user if they have no servers attached to their account.
     *
     * @param  int $id
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function deleteIfNoServers($id)
    {
        $user = $this->getBuilder()->withCount('servers')->where('id', $id)->first();

        if (! $user) {
            throw new RecordNotFoundException();
        }

        if ($user->servers_count > 0) {
            throw new DisplayException('Cannot delete an account that has active servers attached to it.');
        }

        return $user->delete();
    }
}
