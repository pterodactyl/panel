<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Pterodactyl\Contracts\Eggs\DeletesEggs;
use Pterodactyl\Exceptions\Service\Egg\HasChildrenException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Egg;

final class DeleteEgg implements DeletesEggs
{
    /**
     * Delete an egg, provided no servers use it and no eggs inherit their
     * configuration from it.
     *
     * @throws HasActiveServersException
     * @throws HasChildrenException
     */
    public function delete(Egg $egg): void
    {
        if ($egg->servers()->exists()) {
            throw new HasActiveServersException(trans('exceptions.egg.delete_has_servers'));
        }

        if ($egg->configuredChildren()->exists()) {
            throw new HasChildrenException(trans('exceptions.egg.has_children'));
        }

        $egg->delete();
    }
}
