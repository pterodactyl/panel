<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Exceptions\Service\Egg\HasChildrenException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Egg;

interface DeletesEggs
{
    /**
     * An egg that servers use, or that other eggs inherit their configuration from, is not
     * deleted.
     *
     * @throws HasActiveServersException
     * @throws HasChildrenException
     */
    public function delete(Egg $egg): void;
}
