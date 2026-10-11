<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Eggs\CreatesEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Rules\DockerImageList;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Pterodactyl\Support\JsonValueGuard;

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
     * An egg's fields, for creating one and for updating one. The Docker images are one per
     * line, each held to DOCKER_IMAGE (see DockerImageList). An egg that copies its config from
     * another needs none of its own; every config block given must be JSON, apart from the
     * stop command.
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
     * Validates egg input and returns the model attributes it describes. The Docker images
     * become a map of display name to image, a line without a name using the image as its
     * name. A config_from of 0 is the legacy "no parent egg" sentinel, read as null.
     *
     * An egg given no description, outgoing IP flag or features gets none, so an update clears
     * them. The file denylist, the parent egg and the config blocks are left out when they are
     * not given, so a new egg takes the column defaults and an updated one keeps its stored
     * values; a blank one is cleared.
     *
     * @param  ActionInput  $input
     * @return array{name: string, description: string|null, docker_images: array<string, string>, force_outgoing_ip: bool, features: list<string>, startup: string, file_denylist?: list<string>, config_from?: int|null, config_stop?: string|null, config_startup?: string|null, config_logs?: string|null, config_files?: string|null}
     */
    public static function attributes(array $input): array
    {
        if (array_key_exists('config_from', $input) && filter_var($input['config_from'], FILTER_VALIDATE_INT) === 0) {
            $input['config_from'] = null;
        }

        $input = Validator::make($input, self::rules())->safe();

        $attributes = [
            'name' => $input->string('name')->toString(),
            'description' => $input->filled('description') ? $input->string('description')->toString() : null,
            'docker_images' => self::dockerImages($input->string('docker_images')->toString()),
            'force_outgoing_ip' => $input->boolean('force_outgoing_ip'),
            'features' => array_map(fn (int|string $key): string => $input->string('features.'.$key)->toString(), array_keys($input->array('features'))),
            'startup' => $input->string('startup')->toString(),
        ];

        if ($input->has('file_denylist')) {
            $attributes['file_denylist'] = array_map(fn (int|string $key): string => $input->string('file_denylist.'.$key)->toString(), array_keys($input->array('file_denylist')));
        }

        if ($input->has('config_from')) {
            $attributes['config_from'] = $input->filled('config_from') ? $input->integer('config_from') : null;
        }

        if ($input->has('config_stop')) {
            $attributes['config_stop'] = $input->filled('config_stop') ? $input->string('config_stop')->toString() : null;
        }

        if ($input->has('config_startup')) {
            $attributes['config_startup'] = $input->filled('config_startup') ? $input->string('config_startup')->toString() : null;
        }

        if ($input->has('config_logs')) {
            $attributes['config_logs'] = $input->filled('config_logs') ? $input->string('config_logs')->toString() : null;
        }

        if ($input->has('config_files')) {
            $attributes['config_files'] = $input->filled('config_files') ? $input->string('config_files')->toString() : null;
        }

        return $attributes;
    }

    /**
     * Create an egg, stamping the configured service author.
     *
     * @param  ActionInput  $input
     */
    public function create(array $input): Egg
    {
        $attributes = self::attributes($input);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($attributes, $extensions): Egg {
            $egg = Egg::query()->forceCreate([
                ...$attributes,
                'author' => JsonValueGuard::string(Config::get('pterodactyl.service.author')),
            ]);
            $this->extensions->save($egg, $extensions);

            return $egg->refresh();
        });
    }

    /**
     * The images of a newline-separated list, keyed by display name.
     *
     * @return array<string, string>
     */
    private static function dockerImages(string $images): array
    {
        $normalized = [];
        foreach (DockerImageList::lines($images) as $value) {
            $parts = explode('|', $value, 2);
            $normalized[$parts[0]] = empty($parts[1]) ? $parts[0] : $parts[1];
        }

        return $normalized;
    }
}
