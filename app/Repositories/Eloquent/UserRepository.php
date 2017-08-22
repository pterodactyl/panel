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

use Pterodactyl\Models\User;
use Illuminate\Foundation\Application;
use Pterodactyl\Repositories\Concerns\Searchable;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    use Searchable;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

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

    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return User::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllUsersWithCounts()
    {
        $users = $this->getBuilder()->withCount('servers', 'subuserOf');

        if ($this->searchTerm) {
            $users->search($this->searchTerm);
        }

        return $users->paginate(
            $this->config->get('pterodactyl.paginate.admin.users'),
            $this->getColumns()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function filterUsersByQuery($query)
    {
        $this->withColumns([
            'id', 'email', 'username', 'name_first', 'name_last',
        ]);

        $instance = $this->getBuilder()->search($query)->get($this->getColumns());

        return $instance->transform(function ($item) {
            $item->md5 = md5(strtolower($item->email));

            return $item;
        });
    }
}
