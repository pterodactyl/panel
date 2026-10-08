<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Scripts;

use Illuminate\Support\Arr;
use Pterodactyl\Contracts\Eggs\UpdatesEggInstallScripts;
use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Support\JsonValueGuard;

final class UpdateInstallScript implements UpdatesEggInstallScripts
{
    /**
     * Modify the install script for a given Egg.
     *
     * @param  ModelAttributes  $data  validated install script attributes
     *
     * @throws InvalidCopyFromException
     */
    public function update(Egg $egg, array $data): Egg
    {
        $copyFrom = JsonValueGuard::nullableInteger(Arr::get($data, 'copy_script_from'));
        if ($copyFrom !== null && ! Egg::query()->scriptSources()->whereKey($copyFrom)->exists()) {
            throw new InvalidCopyFromException(trans('exceptions.egg.invalid_copy_id'));
        }

        $egg->update([
            'script_install' => JsonValueGuard::nullableString(Arr::get($data, 'script_install')),
            'script_is_privileged' => JsonValueGuard::boolean(Arr::get($data, 'script_is_privileged', true)),
            'script_entry' => JsonValueGuard::string(Arr::get($data, 'script_entry')),
            'script_container' => JsonValueGuard::string(Arr::get($data, 'script_container')),
            'copy_script_from' => $copyFrom,
        ]);

        return $egg;
    }
}
