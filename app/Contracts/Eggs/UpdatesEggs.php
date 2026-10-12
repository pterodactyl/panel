<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;

interface UpdatesEggs
{
    /**
     * The input is validated here and takes the fields an egg is created with, required ones
     * included; a description, outgoing IP flag or features left out are cleared, while the
     * parent egg and config blocks left out keep their stored values. The file denylist cannot
     * be changed here. Extension field values go under `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  the fields an egg is created with
     */
    public function update(Egg $egg, array $input): Egg;
}
