<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Eggs;

use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Support\JsonValueGuard;

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

    public static function fromJson(string $contents): self
    {
        return self::fromValue(JsonValueGuard::decodeArray8($contents));
    }

    /** @param JsonInputValue $value */
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

        return new self(
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
    }

    /**
     * @param  array<string, JsonInputValue>  $root
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
     * @param  JsonInputValue  $value
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
     * @param  JsonInputValue  $value
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
     * @param  JsonInputValue  $value
     * @return array<string, JsonInputValue>
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

    /** @param JsonInputValue $value */
    private static function string(mixed $value, string $field): string
    {
        if (! is_string($value)) {
            throw self::invalid($field);
        }

        return $value;
    }

    /** @param JsonInputValue $value */
    private static function nullableString(mixed $value, string $field): ?string
    {
        return $value === null ? null : self::string($value, $field);
    }

    /** @param JsonInputValue $value */
    private static function boolean(mixed $value, string $field): bool
    {
        if (! is_bool($value)) {
            throw self::invalid($field);
        }

        return $value;
    }

    /**
     * @param  JsonInputValue  $value
     * @return list<string>|null
     */
    private static function nullableStringList(mixed $value, string $field): ?array
    {
        return $value === null ? null : self::stringList($value, $field);
    }

    /**
     * @param  JsonInputValue  $value
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
}
