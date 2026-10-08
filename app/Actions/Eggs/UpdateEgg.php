<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Pterodactyl\Contracts\Eggs\UpdatesEggs;
use Pterodactyl\Models\Egg;

final class UpdateEgg implements UpdatesEggs
{
    /**
     * Update an egg from request-validated attributes.
     *
     * @param  EggCreationData  $data
     */
    public function update(Egg $egg, array $data): Egg
    {
        // TODO(dane): Once the admin UI is done being reworked and this is exposed
        //  in said UI, remove this so that you can actually update the denylist.
        unset($data['file_denylist']);

        $egg->update($data);

        return $egg;
    }
}
