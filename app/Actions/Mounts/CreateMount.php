<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Mounts\CreatesMounts;
use Pterodactyl\Models\Mount;
use Pterodactyl\Rules\NotInPathHierarchy;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Ramsey\Uuid\Uuid;

final readonly class CreateMount implements CreatesMounts
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * A mount's fields. Pass the mount being updated so its own name is not taken for a
     * duplicate. Both paths are absolute. Once '.' and '..' are resolved, the source may not
     * be the Wings configuration directory or anything in it, nor a whole volumes directory,
     * and the target may not be the container's home directory itself.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?Mount $mount = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:64',
                Rule::unique('mounts', 'name')->ignore($mount),
            ],
            'description' => ['nullable', 'string', 'max:191'],

            'source' => [
                'required',
                'string',
                'starts_with:/',
                new NotInPathHierarchy(Mount::$protectedSourcePaths),
                new NotInPathHierarchy(Mount::$invalidSourcePaths, includeDescendants: false),
            ],

            'target' => [
                'required',
                'string',
                'starts_with:/',
                new NotInPathHierarchy(Mount::$invalidTargetPaths, includeDescendants: false),
            ],

            'read_only' => ['sometimes', 'boolean'],
            'user_mountable' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Validates mount input and returns the model attributes it describes. The paths are
     * stored as given. The description and flags are left out when they are not given, so an
     * updated mount keeps its stored values; a blank description is cleared.
     *
     * @param  array<string, mixed>  $input
     * @return array{name: string, source: string, target: string, description?: string|null, read_only?: bool, user_mountable?: bool}
     */
    public static function attributes(array $input, ?Mount $mount = null): array
    {
        $input = Validator::make($input, self::rules($mount))->safe();

        $attributes = [
            'name' => $input->string('name')->toString(),
            'source' => $input->string('source')->toString(),
            'target' => $input->string('target')->toString(),
        ];

        if ($input->has('description')) {
            $attributes['description'] = $input->filled('description') ? $input->string('description')->toString() : null;
        }

        if ($input->has('read_only')) {
            $attributes['read_only'] = $input->boolean('read_only');
        }

        if ($input->has('user_mountable')) {
            $attributes['user_mountable'] = $input->boolean('user_mountable');
        }

        return $attributes;
    }

    /**
     * Create a new mount, assigning it a fresh UUID.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): Mount
    {
        $attributes = self::attributes($input);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($attributes, $extensions): Mount {
            $mount = (new Mount)->fill($attributes);
            $mount->forceFill(['uuid' => Uuid::uuid4()->toString()]);
            $mount->saveOrFail();

            $this->extensions->save($mount, $extensions);

            return $mount;
        });
    }
}
