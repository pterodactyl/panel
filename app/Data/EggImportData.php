<?php

declare(strict_types=1);

namespace Pterodactyl\Data;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Pterodactyl\Actions\Eggs\CreateEgg;
use Pterodactyl\Actions\Eggs\Variables\CreateEggVariable;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Models\Egg;

final readonly class EggImportData
{
    /**
     * @param  list<string>|null  $features
     * @param  array<string, string>  $dockerImages
     * @param  list<string>  $fileDenylist
     * @param  list<EggImportVariable>  $variables
     */
    public function __construct(
        public string $name,
        public string $author,
        public ?string $description,
        public ?array $features,
        public array $dockerImages,
        public array $fileDenylist,
        public ?string $updateUrl,
        public ?string $configFiles,
        public ?string $configStartup,
        public ?string $configLogs,
        public ?string $configStop,
        public ?string $startup,
        public ?string $scriptInstall,
        public string $scriptEntry,
        public string $scriptContainer,
        public array $variables,
    ) {}

    /**
     * @throws InvalidFileUploadException
     * @throws JsonException
     */
    public static function fromFile(UploadedFile $file): self
    {
        throw_if($file->getError() !== UPLOAD_ERR_OK || ! $file->isFile(), InvalidFileUploadException::class, 'The selected file is not valid and cannot be imported.');

        $contents = $file->openFile()->fread($file->getSize());
        throw_if($contents === false, InvalidFileUploadException::class, 'The selected file could not be read.');

        return self::fromJson($contents);
    }

    public static function fromJson(string $contents): self
    {
        return self::fromValue(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * The egg attributes an import sets, both on a new egg and on one it updates.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'features' => $this->features,
            'docker_images' => $this->dockerImages,
            'file_denylist' => $this->fileDenylist,
            'update_url' => $this->updateUrl,
            'config_files' => $this->configFiles,
            'config_startup' => $this->configStartup,
            'config_logs' => $this->configLogs,
            'config_stop' => $this->configStop,
            'startup' => $this->startup,
            'script_install' => $this->scriptInstall,
            'script_entry' => $this->scriptEntry,
            'script_container' => $this->scriptContainer,
        ];
    }

    private static function fromValue(mixed $value): self
    {
        $root = self::object($value, 'egg');
        $meta = self::object($root['meta'] ?? null, 'meta');
        $version = self::string($meta['version'] ?? null, 'meta.version');

        if (! in_array($version, ['PTDL_v1', Egg::EXPORT_VERSION], true)) {
            throw self::invalid('meta.version');
        }

        $config = self::object($root['config'] ?? null, 'config');
        $scripts = self::object($root['scripts'] ?? null, 'scripts');
        $installation = self::object($scripts['installation'] ?? null, 'scripts.installation');

        $data = new self(
            name: self::string($root['name'] ?? null, 'name'),
            author: self::string($root['author'] ?? null, 'author'),
            description: self::nullableString($root['description'] ?? null, 'description'),
            features: self::nullableStringList($root['features'] ?? null, 'features'),
            dockerImages: self::dockerImages($root, $version),
            fileDenylist: self::fileDenylist($root['file_denylist'] ?? null),
            updateUrl: self::nullableString($meta['update_url'] ?? null, 'meta.update_url'),
            configFiles: self::nullableString($config['files'] ?? null, 'config.files'),
            configStartup: self::nullableString($config['startup'] ?? null, 'config.startup'),
            configLogs: self::nullableString($config['logs'] ?? null, 'config.logs'),
            configStop: self::nullableString($config['stop'] ?? null, 'config.stop'),
            startup: self::nullableString($root['startup'] ?? null, 'startup'),
            scriptInstall: self::nullableString($installation['script'] ?? null, 'scripts.installation.script'),
            scriptEntry: self::string($installation['entrypoint'] ?? null, 'scripts.installation.entrypoint'),
            scriptContainer: self::string($installation['container'] ?? null, 'scripts.installation.container'),
            variables: self::variables($root['variables'] ?? []),
        );
        $data->validate();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $root
     * @return array<string, string>
     */
    private static function dockerImages(array $root, string $version): array
    {
        if ($version === Egg::EXPORT_VERSION) {
            $images = self::object($root['docker_images'] ?? null, 'docker_images');
            $normalized = [];
            foreach ($images as $label => $image) {
                $normalized[$label] = self::string($image, 'docker_images.'.$label);
            }

            return $normalized;
        }

        $legacy = $root['images'] ?? $root['image'] ?? null;
        $images = is_string($legacy) ? [$legacy] : self::stringList($legacy, 'images');
        $normalized = [];
        foreach ($images as $image) {
            $normalized[$image] = $image;
        }

        return $normalized;
    }

    /**
     * @return list<EggImportVariable>
     */
    private static function variables(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw self::invalid('variables');
        }

        $variables = [];
        foreach ($value as $index => $item) {
            $variable = self::object($item, 'variables.'.$index);
            $variables[] = new EggImportVariable(
                name: self::string($variable['name'] ?? null, "variables.$index.name"),
                description: self::string($variable['description'] ?? null, "variables.$index.description"),
                environmentVariable: self::string($variable['env_variable'] ?? null, "variables.$index.env_variable"),
                defaultValue: self::string($variable['default_value'] ?? null, "variables.$index.default_value"),
                userViewable: self::boolean($variable['user_viewable'] ?? null, "variables.$index.user_viewable"),
                userEditable: self::boolean($variable['user_editable'] ?? null, "variables.$index.user_editable"),
                rules: self::string($variable['rules'] ?? null, "variables.$index.rules"),
            );
        }

        return $variables;
    }

    /**
     * @return list<string>
     */
    private static function fileDenylist(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        $values = is_string($value) ? [$value] : self::stringList($value, 'file_denylist');

        return array_values(array_filter($values, fn (string $item): bool => ! empty($item)));
    }

    /**
     * @return array<string, mixed>
     */
    private static function object(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw self::invalid($field);
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (! is_string($key)) {
                throw self::invalid($field);
            }

            $object[$key] = $item;
        }

        return $object;
    }

    private static function string(mixed $value, string $field): string
    {
        if (! is_string($value)) {
            throw self::invalid($field);
        }

        return $value;
    }

    private static function nullableString(mixed $value, string $field): ?string
    {
        return $value === null ? null : self::string($value, $field);
    }

    private static function boolean(mixed $value, string $field): bool
    {
        if (! is_bool($value)) {
            throw self::invalid($field);
        }

        return $value;
    }

    /**
     * @return list<string>|null
     */
    private static function nullableStringList(mixed $value, string $field): ?array
    {
        return $value === null ? null : self::stringList($value, $field);
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value, string $field): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw self::invalid($field);
        }

        $strings = [];
        foreach ($value as $item) {
            $strings[] = self::string($item, $field);
        }

        return $strings;
    }

    private static function invalid(string $field): InvalidFileUploadException
    {
        return new InvalidFileUploadException(sprintf('The egg field "%s" is missing or invalid.', $field));
    }

    /**
     * The rules an egg read from an export file must pass, keyed like its model attributes.
     * Exports carry their own config rather than inheriting one, so every config block is
     * checked as JSON. Each image and each variable is held to the rules of one written
     * through the admin API. An imported variable carries its user_viewable and user_editable
     * flags rather than options, and its environment variable name must differ from the
     * others'.
     *
     * @return NormalizedValidationRules
     */
    private function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:191'],
            'author' => ['required', 'string', 'email'],
            'docker_images' => ['required', 'array', 'min:1'],
            'docker_images.*' => CreateEgg::DOCKER_IMAGE,
            'startup' => ['nullable', 'string'],
            'config_stop' => ['nullable', 'string', 'max:191'],
            'config_startup' => ['nullable', 'json'],
            'config_logs' => ['nullable', 'json'],
            'config_files' => ['nullable', 'json'],
            'variables' => ['array'],
        ];

        $variable = Arr::only(CreateEggVariable::rules(), ['name', 'description', 'env_variable', 'rules', 'default_value']);
        foreach ($variable as $field => $fieldRules) {
            $rules["variables.*.{$field}"] = $fieldRules;
        }

        $rules['variables.*.env_variable'][] = 'distinct';

        return $rules;
    }

    /**
     * Holds an imported egg to the rules an egg written through the admin API meets, so a
     * file cannot store config that every node serving the egg would later fail to read.
     */
    private function validate(): void
    {
        $validator = Validator::make([
            'name' => $this->name,
            'author' => $this->author,
            'docker_images' => $this->dockerImages,
            'startup' => $this->startup,
            'config_stop' => $this->configStop,
            'config_startup' => $this->configStartup,
            'config_logs' => $this->configLogs,
            'config_files' => $this->configFiles,
            'variables' => array_map(fn (EggImportVariable $variable): array => $variable->attributes(), $this->variables),
        ], $this->rules());

        if ($validator->fails()) {
            $errors = $validator->errors();
            $field = $errors->keys()[0];

            throw new InvalidFileUploadException(sprintf('The egg field "%s" is invalid: %s', $field, $errors->first($field)));
        }
    }
}
