<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
