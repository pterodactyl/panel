<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Scripts;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Eggs\UpdatesEggInstallScripts;
use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;
use Pterodactyl\Models\Egg;

final class UpdateInstallScript implements UpdatesEggInstallScripts
{
    /**
     * An egg's install script fields, each of them optional: the script, whether it runs
     * privileged, the container entrypoint and image it runs in, and the egg to copy the
     * script from instead.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'script_install' => ['sometimes', 'nullable', 'string'],
            'script_is_privileged' => ['sometimes', 'required', 'boolean'],
            'script_entry' => ['sometimes', 'required', 'string'],
            'script_container' => ['sometimes', 'required', 'string'],
            'copy_script_from' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * Modify the install script for a given Egg.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws InvalidCopyFromException
     */
    public function update(Egg $egg, array $input): Egg
    {
        $attributes = Validator::validate($input, self::rules());
        // Blank strings are null, as they are in a request, so a blank script or copy source is cleared.
        $attributes = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $attributes);

        $copyFrom = $attributes['copy_script_from'] ?? null;
        if ($copyFrom !== null && ! Egg::query()->scriptSources()->whereKey($copyFrom)->exists()) {
            throw new InvalidCopyFromException(trans('exceptions.egg.invalid_copy_id'));
        }

        $egg->update($attributes);

        return $egg;
    }
}
