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
use Pterodactyl\Models\Service;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class ServiceRepository extends EloquentRepository implements ServiceRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Service::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithOptions($id = null)
    {
        Assert::nullOrNumeric($id, 'First argument passed to getWithOptions must be null or numeric, received %s.');

        $instance = $this->getBuilder()->with('options.packs', 'options.variables');

        if (! is_null($id)) {
            $instance = $instance->find($id, $this->getColumns());
            if (! $instance) {
                throw new RecordNotFoundException();
            }

            return $instance;
        }

        return $instance->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getWithOptionServers($id)
    {
        Assert::numeric($id, 'First argument passed to getWithOptionServers must be numeric, received %s.');

        $instance = $this->getBuilder()->with('options.servers')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }
}
