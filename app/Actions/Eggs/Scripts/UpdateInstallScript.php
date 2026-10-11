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
     * Validates install script input and returns the attributes it changes: only the fields
     * given, so the rest keep their stored values. A blank script or copy source is cleared.
     *
     * @param  array<string, mixed>  $input
     * @return array{script_install?: string|null, script_is_privileged?: bool, script_entry?: string, script_container?: string, copy_script_from?: int|null}
     */
    public static function attributes(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();

        $attributes = [];
        if ($input->has('script_install')) {
            $attributes['script_install'] = $input->filled('script_install') ? $input->string('script_install')->toString() : null;
        }

        if ($input->has('script_is_privileged')) {
            $attributes['script_is_privileged'] = $input->boolean('script_is_privileged');
        }

        if ($input->has('script_entry')) {
            $attributes['script_entry'] = $input->string('script_entry')->toString();
        }

        if ($input->has('script_container')) {
            $attributes['script_container'] = $input->string('script_container')->toString();
        }

        if ($input->has('copy_script_from')) {
            $attributes['copy_script_from'] = $input->filled('copy_script_from') ? $input->integer('copy_script_from') : null;
        }

        return $attributes;
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
        $attributes = self::attributes($input);

        $copyFrom = $attributes['copy_script_from'] ?? null;
        if ($copyFrom !== null && ! Egg::query()->scriptSources()->whereKey($copyFrom)->exists()) {
            throw new InvalidCopyFromException(trans('exceptions.egg.invalid_copy_id'));
        }

        $egg->update($attributes);

        return $egg;
    }
}
