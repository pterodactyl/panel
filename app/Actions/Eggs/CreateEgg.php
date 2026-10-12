<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Eggs\CreatesEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Rules\DockerImageList;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class CreateEgg implements CreatesEggs
{
    /**
     * Rules every Docker image an egg offers must pass, wherever it is set: an image,
     * optionally prefixed with its display name, such as `Java 21|ghcr.io/...`.
     *
     * @var list<string>
     */
    public const array DOCKER_IMAGE = ['required', 'string', 'max:191', 'regex:/^[\w#\.\/\- ]*\|?~?[\w\.\/\-:@ ]*$/'];

    public function __construct(private ExtensionFields $extensions) {}

    /**
     * The Docker images are one per line, each held to DOCKER_IMAGE. An egg that copies its
     * config from another needs none of its own.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'docker_images' => ['required', 'string', new DockerImageList],
            'force_outgoing_ip' => ['sometimes', 'boolean'],
            'file_denylist' => ['array'],
            'file_denylist.*' => ['string'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string'],
            'startup' => ['required', 'string'],
            'config_from' => ['sometimes', 'bail', 'nullable', 'integer', Rule::exists(Egg::class, 'id')],
            'config_stop' => ['required_without:config_from', 'nullable', 'string', 'max:191'],
            'config_startup' => ['required_without:config_from', 'nullable', 'json'],
            'config_logs' => ['required_without:config_from', 'nullable', 'json'],
            'config_files' => ['required_without:config_from', 'nullable', 'json'],
        ];
    }

    /**
     * The images of a newline-separated list, keyed by display name. A line without a name
     * uses the image as its name.
     *
     * @return array<string, string>
     */
    public static function dockerImages(string $images): array
    {
        $normalized = [];
        foreach (DockerImageList::lines($images) as $value) {
            $parts = explode('|', $value, 2);
            $normalized[$parts[0]] = empty($parts[1]) ? $parts[0] : $parts[1];
        }

        return $normalized;
    }

    public function create(array $input): Egg
    {
        // A parent egg of 0 is the legacy "no parent egg" sentinel.
        if (filter_var($input['config_from'] ?? null, FILTER_VALIDATE_INT) === 0) {
            $input['config_from'] = null;
        }

        $validated = Validator::validate($input, self::rules());
        // Blank strings are null, as they are in a request.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($validated, $extensions): Egg {
            $egg = Egg::query()->forceCreate([
                ...$validated,
                'docker_images' => self::dockerImages(Arr::string($validated, 'docker_images')),
                'force_outgoing_ip' => $validated['force_outgoing_ip'] ?? false,
                'features' => $validated['features'] ?? [],
                'author' => Config::string('pterodactyl.service.author'),
            ]);
            $this->extensions->save($egg, $extensions);

            return $egg->refresh();
        });
    }
}
