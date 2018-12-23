<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Egg;
use Illuminate\Database\Eloquent\Collection;

interface EggRepositoryInterface extends RepositoryInterface
{
    /**
     * Return an egg with the variables relation attached.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Egg
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithVariables(int $id): Egg;

    /**
     * Return all eggs and their relations to be used in the daemon API.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllWithCopyAttributes(): Collection;

    /**
     * Return an egg with the scriptFrom and configFrom relations loaded onto the model.
     *
     * @param int|string $value
     * @param string     $column
     * @return \Pterodactyl\Models\Egg
     */
    public function getWithCopyAttributes($value, string $column = 'id'): Egg;

    /**
     * Return all of the data needed to export a service.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Egg
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithExportAttributes(int $id): Egg;

    /**
     * Confirm a copy script belongs to the same nest as the item trying to use it.
     *
     * @param int $copyFromId
     * @param int $service
     * @return bool
     */
    public function isCopyableScript(int $copyFromId, int $service): bool;
}
