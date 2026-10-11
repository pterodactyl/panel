<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;
use Pterodactyl\Models\Egg;

interface UpdatesEggInstallScripts
{
    /**
     * Modify the install script for a given Egg. The input is validated here, so it can come
     * straight from any caller, and only the fields given are changed. An egg to copy the
     * script from must have a script of its own.
     *
     * @param  array<string, mixed>  $input  any of script_install, script_is_privileged, script_entry, script_container and copy_script_from
     *
     * @throws InvalidCopyFromException
     */
    public function update(Egg $egg, array $input): Egg;
}
