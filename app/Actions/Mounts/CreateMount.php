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
     * Pass the mount being updated so its own name is not taken for a duplicate. Once '.' and
     * '..' are resolved, the source may not be the Wings configuration directory or anything in
     * it, nor a whole volumes directory, and the target may not be the container's home
     * directory itself.
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

    public function create(array $input): Mount
    {
        $validated = Validator::validate($input, self::rules());
        // Blank strings are null, as they are in a request.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($validated, $extensions): Mount {
            $mount = (new Mount)->fill($validated);
            $mount->forceFill(['uuid' => Uuid::uuid4()->toString()]);
            $mount->saveOrFail();

            $this->extensions->save($mount, $extensions);

            return $mount;
        });
    }
}
