<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Egg;
use Webmozart\Assert\Assert;
use Illuminate\Database\Eloquent\Collection;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class EggRepository extends EloquentRepository implements EggRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Egg::class;
    }

    /**
     * Return an egg with the variables relation attached.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Egg
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithVariables(int $id): Egg
    {
        /** @var \Pterodactyl\Models\Egg $instance */
        $instance = $this->getBuilder()->with('variables')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * Return all eggs and their relations to be used in the daemon API.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllWithCopyAttributes(): Collection
    {
        return $this->getBuilder()->with('scriptFrom', 'configFrom')->get($this->getColumns());
    }

    /**
     * Return an egg with the scriptFrom and configFrom relations loaded onto the model.
     *
     * @param int|string $value
     * @param string     $column
     * @return \Pterodactyl\Models\Egg
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCopyAttributes($value, string $column = 'id'): Egg
    {
        Assert::true((is_digit($value) || is_string($value)), 'First argument passed to getWithCopyAttributes must be an integer or string, received %s.');

        /** @var \Pterodactyl\Models\Egg $instance */
        $instance = $this->getBuilder()->with('scriptFrom', 'configFrom')->where($column, '=', $value)->first($this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * Return all of the data needed to export a service.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Egg
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithExportAttributes(int $id): Egg
    {
        /** @var \Pterodactyl\Models\Egg $instance */
        $instance = $this->getBuilder()->with('scriptFrom', 'configFrom', 'variables')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * Confirm a copy script belongs to the same nest as the item trying to use it.
     *
     * @param int $copyFromId
     * @param int $service
     * @return bool
     */
    public function isCopiableScript(int $copyFromId, int $service): bool
    {
        return $this->getBuilder()->whereNull('copy_script_from')
            ->where('id', '=', $copyFromId)
            ->where('nest_id', '=', $service)
            ->exists();
    }
}
