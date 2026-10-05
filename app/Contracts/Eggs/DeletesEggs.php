<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Exceptions\Service\Egg\HasChildrenException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Egg;

interface DeletesEggs
{
    /**
     * Delete an egg, provided no servers use it and no eggs inherit their
     * configuration from it.
     *
     * @throws HasActiveServersException
     * @throws HasChildrenException
     */
    public function delete(Egg $egg): void;
}
