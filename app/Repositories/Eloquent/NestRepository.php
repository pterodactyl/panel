<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Nest;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class NestRepository extends EloquentRepository implements NestRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Nest::class;
    }

    /**
     * Return a nest or all nests with their associated eggs and variables.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggs(int $id = null): Nest
    {
        $instance = $this->getBuilder()->with('eggs', 'eggs.variables');

        if (!is_null($id)) {
            /** @var \Pterodactyl\Models\Nest|null $instance */
            $instance = $instance->find($id, $this->getColumns());
            if (!$instance) {
                throw new RecordNotFoundException();
            }

            return $instance;
        }

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        // @phpstan-ignore-next-line
        return $instance->get($this->getColumns());
    }
}
