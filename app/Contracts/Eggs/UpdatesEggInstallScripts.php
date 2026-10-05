<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;
use Pterodactyl\Models\Egg;

interface UpdatesEggInstallScripts
{
    /**
     * Modify the install script for a given Egg.
     *
     * @param  ModelAttributes  $data  validated install script attributes
     *
     * @throws InvalidCopyFromException
     */
    public function update(Egg $egg, array $data): void;
}
