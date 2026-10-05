<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support;

use Symfony\Component\Yaml\Yaml;

/**
 * Validates real JSON responses produced by the integration suite against the
 * generated OpenAPI specification, so the documented contract and the actual
 * API cannot drift apart silently.
 *
 * Supports the schema dialect the panel's spec generator emits: $ref, type,
 * properties/required/additionalProperties, items, oneOf, enum, and the
 * OpenAPI 3.0 nullable flag.
 */
class OpenApiSpecValidator
{
    /** @var array<string, mixed>|null */
    private static ?array $spec = null;

    /** @var array<string, array<string, mixed>>|null map of "METHOD /uri{}" to operation */
    private static ?array $operations = null;

    public static function specPath(): string
    {
        return storage_path('app/private/scribe/openapi.yaml');
    }

    public static function available(): bool
    {
        return is_file(self::specPath());
    }

    /**
     * Returns a list of contract violations for the response, or an empty
     * array when the response matches the documented schema. Responses for
     * routes that are not part of the spec are ignored.
     *
     * @return string[]
     */
    public static function validateResponse(string $method, string $routeUri, int $status, string $content): array
    {
        $operation = self::operations()[self::key($method, $routeUri)] ?? null;
        if ($operation === null) {
            return [];
        }

        if ($status < 200 || $status >= 300 || $status === 204) {
            return [];
        }

        $response = $operation['responses'][$status] ?? $operation['responses'][(string) $status] ?? null;
        if ($response === null) {
            return ["response status {$status} is not documented for this operation"];
        }

        $schema = $response['content']['application/json']['schema'] ?? null;
        if ($schema === null) {
            // Documented as a non-JSON or empty response; nothing to check.
            return [];
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return ['response body is not a JSON object while the spec documents a JSON schema'];
        }

        $errors = [];
        self::validate($decoded, $schema, '$', $errors);

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  string[]  $errors
     */
    private static function validate(mixed $value, array $schema, string $path, array &$errors): void
    {
        if (isset($schema['$ref'])) {
            $resolved = self::resolveRef($schema['$ref']);
            if ($resolved === null) {
                $errors[] = "{$path}: unresolvable \$ref {$schema['$ref']}";

                return;
            }

            self::validate($value, $resolved, $path, $errors);

            return;
        }

        if ($value === null && ($schema['nullable'] ?? false) === true) {
            return;
        }

        if (isset($schema['oneOf']) && is_array($schema['oneOf'])) {
            foreach ($schema['oneOf'] as $branch) {
                $branchErrors = [];
                self::validate($value, $branch, $path, $branchErrors);
                if ($branchErrors === []) {
                    return;
                }
            }

            $errors[] = "{$path}: value matches no oneOf branch";

            return;
        }

        if ($value === null) {
            if (($schema['nullable'] ?? false) !== true) {
                $errors[] = "{$path}: null is not allowed (schema is not nullable)";
            }

            return;
        }

        if (isset($schema['enum']) && is_array($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            $errors[] = "{$path}: value ".json_encode($value).' is not one of the documented enum values';

            return;
        }

        match ($schema['type'] ?? null) {
            'integer' => is_int($value) || $errors[] = "{$path}: expected integer, got ".get_debug_type($value),
            'number' => is_int($value) || is_float($value) || $errors[] = "{$path}: expected number, got ".get_debug_type($value),
            'string' => is_string($value) || $errors[] = "{$path}: expected string, got ".get_debug_type($value),
            'boolean' => is_bool($value) || $errors[] = "{$path}: expected boolean, got ".get_debug_type($value),
            'array' => self::validateArray($value, $schema, $path, $errors),
            'object' => self::validateObject($value, $schema, $path, $errors),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  string[]  $errors
     */
    private static function validateArray(mixed $value, array $schema, string $path, array &$errors): void
    {
        if (! is_array($value) || ! array_is_list($value)) {
            $errors[] = "{$path}: expected array, got ".get_debug_type($value);

            return;
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            foreach ($value as $index => $item) {
                self::validate($item, $schema['items'], "{$path}[{$index}]", $errors);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  string[]  $errors
     */
    private static function validateObject(mixed $value, array $schema, string $path, array &$errors): void
    {
        // json_decode(assoc: true) turns objects into arrays; an empty object
        // is indistinguishable from an empty list and both are acceptable.
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            $errors[] = "{$path}: expected object, got ".get_debug_type($value);

            return;
        }

        foreach (($schema['required'] ?? []) as $key) {
            if (! array_key_exists($key, $value)) {
                $errors[] = "{$path}: missing required property [{$key}]";
            }
        }

        $properties = $schema['properties'] ?? [];
        foreach ($value as $key => $item) {
            if (isset($properties[$key]) && is_array($properties[$key])) {
                self::validate($item, $properties[$key], "{$path}.{$key}", $errors);
            } elseif (isset($schema['additionalProperties']) && is_array($schema['additionalProperties'])) {
                self::validate($item, $schema['additionalProperties'], "{$path}.{$key}", $errors);
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function resolveRef(string $ref): ?array
    {
        if (! str_starts_with($ref, '#/components/schemas/')) {
            return null;
        }

        $name = mb_substr($ref, mb_strlen('#/components/schemas/'));
        $schema = self::spec()['components']['schemas'][$name] ?? null;

        return is_array($schema) ? $schema : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function spec(): array
    {
        return self::$spec ??= Yaml::parseFile(self::specPath());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function operations(): array
    {
        if (self::$operations !== null) {
            return self::$operations;
        }

        $operations = [];
        foreach (self::spec()['paths'] ?? [] as $uri => $pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (is_array($operation) && in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $operations[self::key($method, (string) $uri)] = $operation;
                }
            }
        }

        return self::$operations = $operations;
    }

    private static function key(string $method, string $uri): string
    {
        $uri = preg_replace('/\{[^}]+\}/', '{}', mb_trim($uri, '/'));

        return mb_strtoupper($method).' /'.$uri;
    }
}
