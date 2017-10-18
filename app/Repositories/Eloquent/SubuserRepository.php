<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class SubuserRepository extends EloquentRepository implements SubuserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Subuser::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithServer($id)
    {
        Assert::numeric($id, 'First argument passed to getWithServer must be numeric, received %s.');

        $instance = $this->getBuilder()->with('server', 'user')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithPermissions($id)
    {
        Assert::numeric($id, 'First argument passed to getWithPermissions must be numeric, received %s.');

        $instance = $this->getBuilder()->with('permissions', 'user')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithPermissionsUsingUserAndServer($user, $server)
    {
        Assert::integerish($user, 'First argument passed to getWithPermissionsUsingUserAndServer must be integer, received %s.');
        Assert::integerish($server, 'Second argument passed to getWithPermissionsUsingUserAndServer must be integer, received %s.');

        $instance = $this->getBuilder()->with('permissions')->where([
            ['user_id', '=', $user],
            ['server_id', '=', $server],
        ])->first();

        if (is_null($instance)) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithServerAndPermissions($id)
    {
        Assert::numeric($id, 'First argument passed to getWithServerAndPermissions must be numeric, received %s.');

        $instance = $this->getBuilder()->with('server', 'permission', 'user')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithKey($user, $server)
    {
        Assert::integerish($user, 'First argument passed to getWithKey must be integer, received %s.');
        Assert::integerish($server, 'Second argument passed to getWithKey must be integer, received %s.');

        $instance = $this->getBuilder()->with('key')->where([
            ['user_id', '=', $user],
            ['server_id', '=', $server],
        ])->first();

        if (is_null($instance)) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }
}
