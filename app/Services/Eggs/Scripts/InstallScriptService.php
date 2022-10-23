<?php

namespace Pterodactyl\Services\Eggs\Scripts;

use Pterodactyl\Models\Egg;
use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;

class InstallScriptService
{
    /**
     * Modify the installation script for a given Egg.
     *
     * @throws InvalidCopyFromException
     */
    public function handle(Egg $egg, array $data): void
    {
        $copyFromEggId = array_get($data, 'copy_script_from');
        if (!is_null($copyFromEggId)) {
            $isCopyableScript = $egg->nest->eggs()
                ->where('id', $copyFromEggId)
                ->whereNull('copy_script_from')
                ->exists();

            if (!$isCopyableScript) {
                throw new InvalidCopyFromException(trans('exceptions.nest.egg.invalid_copy_id'));
            }
        }

        $egg->update([
            'script_install' => array_get($data, 'script_install'),
            'script_is_privileged' => array_get($data, 'script_is_privileged', 1),
            'script_entry' => array_get($data, 'script_entry'),
            'script_container' => array_get($data, 'script_container'),
            'copy_script_from' => array_get($data, 'copy_script_from'),
        ]);
    }
}
