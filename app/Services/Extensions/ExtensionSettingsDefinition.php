<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use InvalidArgumentException;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\Contracts\ManagesExtensionSettings;

class ExtensionSettingsDefinition implements ManagesExtensionSettings
{
    /** @var array<string, ExtensionSettingDefinition> */
    private array $definitions;

    /** @param array<int, ExtensionSettingDefinition> $definitions */
    public function __construct(
        private readonly ExtensionSettings $settings,
        array $definitions,
    ) {
        $this->definitions = [];
        foreach ($definitions as $definition) {
            $this->definitions[$definition->key] = $definition;
        }
    }

    public function forUser(User $user): self
    {
        return new self($this->settings->forUser($user), array_values($this->definitions));
    }

    public function forServer(Server $server): self
    {
        return new self($this->settings->forServer($server), array_values($this->definitions));
    }

    /** The id of the extension these settings belong to. */
    public function extension(): string
    {
        return $this->settings->extension();
    }

    /**
     * The setting's current value. A file setting reads as the public URL of
     * its uploaded file, or its default while nothing is uploaded.
     *
     * @return ExtensionSettingValue
     */
    public function get(string $key): mixed
    {
        $definition = $this->definitions[$key] ?? null;
        if ($definition === null) {
            throw new InvalidArgumentException(sprintf('Unknown extension setting "%s".', $key));
        }

        if ($definition->isFile()) {
            $name = $this->storedFile($definition);

            return $definition->normalize($name === null ? $definition->default : ExtensionSettingFiles::url($this->extension(), $name));
        }

        return $definition->normalize($this->settings->get($definition->key, $definition->default));
    }

    /** @return ExtensionSettingValues */
    public function publicSettings(): array
    {
        $values = [];
        foreach ($this->definitions as $definition) {
            $values[$definition->publicName()] = $definition->serializePublic($this->get($definition->key));
        }

        return $values;
    }

    /**
     * Rules for a partial update: every input is optional and, when submitted, must pass
     * its definition's rules and those of its field type. Inputs without rules of their
     * own are accepted as given. File inputs are prohibited: files are uploaded and
     * cleared through replaceFile().
     *
     * @return NormalizedValidationRules
     */
    public function validationRules(): array
    {
        $rules = [];
        foreach ($this->definitions as $definition) {
            $rules = [...$rules, ...$definition->validationRules()];
        }

        return $rules;
    }

    /** @param ExtensionSettingValues $input */
    public function update(array $input): void
    {
        $input = $this->withoutBlankSecrets($input);
        $values = [];
        $secretKeys = [];
        foreach ($this->definitions as $definition) {
            if ($definition->isFile() || ! array_key_exists($definition->input, $input)) {
                continue;
            }

            $values[$definition->key] = $definition->normalize($input[$definition->input]);
            if ($definition->isSecret()) {
                $secretKeys[] = $definition->key;
            }
        }

        if ($secretKeys === []) {
            $this->settings->setMany($values);

            return;
        }

        $this->settings->setManySecrets($values, $secretKeys);
    }

    /**
     * @param  ExtensionSettingValues  $input
     * @return ExtensionSettingValues
     */
    public function withoutBlankSecrets(array $input): array
    {
        foreach ($this->definitions as $definition) {
            if ($definition->isSecret() && in_array($input[$definition->input] ?? null, ['', null], true)) {
                unset($input[$definition->input]);
            }
        }

        return $input;
    }

    /**
     * Field schema for the auto-rendered admin settings form, in definition
     * order, with each entry carrying its current public value.
     *
     * @return list<ExtensionSettingField>
     */
    public function schema(): array
    {
        return array_values(array_map(
            fn (ExtensionSettingDefinition $definition): array => $definition->describe($this->get($definition->key)),
            $this->definitions,
        ));
    }

    /** The file setting behind an input name, or null when the input is not a file setting. */
    public function fileField(string $input): ?ExtensionSettingDefinition
    {
        return array_find($this->definitions, fn (ExtensionSettingDefinition $definition): bool => $definition->input === $input && $definition->isFile());
    }

    /**
     * Point a file setting at a newly stored file, or clear it with null.
     *
     * @return string|null the stored name it pointed at before, which the caller deletes
     */
    public function replaceFile(string $input, ?string $name): ?string
    {
        $definition = $this->fileField($input);
        throw_unless($definition instanceof ExtensionSettingDefinition, InvalidArgumentException::class, sprintf('Extension setting "%s" is not a file setting.', $input));
        throw_if($name !== null && ExtensionSettingValueGuard::fileReference($name) === null, InvalidArgumentException::class, 'Invalid stored file name.');

        $previous = $this->storedFile($definition);
        if ($name === null) {
            $this->settings->forget($definition->key);
        } else {
            $this->settings->set($definition->key, $name);
        }

        return $previous;
    }

    /**
     * Values exposed to the extension's frontend bundle as ctx.config: only
     * definitions explicitly marked ->frontend(), keyed by public name. These
     * are visible to every logged-in user. With $publicOnly, only those also
     * marked ->public(), which is what visitors who are not signed in receive.
     *
     * @return ExtensionSettingValues
     */
    public function frontendConfig(bool $publicOnly = false): array
    {
        $config = [];
        foreach ($this->definitions as $definition) {
            if (! $definition->isFrontend() || ($publicOnly && ! $definition->isPublic())) {
                continue;
            }

            $value = $definition->serializePublic($this->get($definition->key));
            ExtensionSettingValueGuard::assertFrontendType($value, $definition->declaredFrontendType());
            $config[$definition->publicName()] = $value;
        }

        return $config;
    }

    /** @return array<string, 'string'|'number'|'boolean'|'array'|'object'|'null'|'json'> */
    public function frontendConfigTypes(): array
    {
        $types = [];
        foreach ($this->definitions as $definition) {
            if ($definition->isFrontend()) {
                $types[$definition->publicName()] = $definition->declaredFrontendType();
            }
        }

        return $types;
    }

    /** @return list<string> public names of the frontend settings that guests receive too */
    public function publicConfigKeys(): array
    {
        return array_values(array_map(
            fn (ExtensionSettingDefinition $definition): string => $definition->publicName(),
            array_filter($this->definitions, fn (ExtensionSettingDefinition $definition): bool => $definition->isPublic()),
        ));
    }

    private function storedFile(ExtensionSettingDefinition $definition): ?string
    {
        return ExtensionSettingValueGuard::fileReference($this->settings->get($definition->key));
    }
}
