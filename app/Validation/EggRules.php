<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

final class EggRules
{
    /**
     * A single Docker image, optionally prefixed with its display name: `Java 21|ghcr.io/...`.
     *
     * @var list<string>
     */
    public const array DOCKER_IMAGE = ['required', 'string', 'max:191', 'regex:/^[\w#\.\/\- ]*\|?~?[\w\.\/\-:@ ]*$/'];

    /**
     * Validation rules for an egg read from an export file, keyed like its model attributes.
     * Exports carry their own config rather than inheriting one, so every config block is
     * checked as JSON.
     *
     * @return NormalizedValidationRules
     */
    public static function import(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:191'],
            'author' => ['required', 'string', 'email'],
            'docker_images' => ['required', 'array', 'min:1'],
            'docker_images.*' => self::DOCKER_IMAGE,
            'startup' => ['nullable', 'string'],
            'config_stop' => ['nullable', 'string', 'max:191'],
            'config_startup' => ['nullable', 'json'],
            'config_logs' => ['nullable', 'json'],
            'config_files' => ['nullable', 'json'],
            'variables' => ['array'],
        ];

        foreach (EggVariableRules::rules() as $field => $fieldRules) {
            $rules["variables.*.{$field}"] = $fieldRules;
        }

        $rules['variables.*.env_variable'][] = 'distinct';

        return $rules;
    }
}
