<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

$extension = $argv[1] ?? null;
if (! is_string($extension) || ! preg_match('/^[a-z0-9][a-z0-9-]*$/', $extension)) {
    fwrite(STDERR, "Usage: php scripts/generate-extension-openapi.php <extension-id> [input] [output]\n");
    exit(1);
}

$root = dirname(__DIR__);
$input = $argv[2] ?? $root.'/storage/app/private/scribe/openapi.yaml';
$output = $argv[3] ?? $root.'/extensions/'.$extension.'/openapi.yaml';

if (! is_file($input)) {
    fwrite(STDERR, "OpenAPI spec not found at {$input}. Run composer docs:openapi first.\n");
    exit(1);
}

$inputIsJson = mb_strtolower(pathinfo($input, PATHINFO_EXTENSION)) === 'json';
$outputIsJson = mb_strtolower(pathinfo($output, PATHINFO_EXTENSION)) === 'json';
if (! $inputIsJson || ! $outputIsJson) {
    $autoload = $root.'/vendor/autoload.php';
    if (! is_file($autoload)) {
        fwrite(STDERR, "YAML input or output requires Composer dependencies. Run composer install or use JSON files.\n");
        exit(1);
    }

    require $autoload;
}

$document = $inputIsJson
    ? json_decode(file_get_contents($input), false, 512, JSON_THROW_ON_ERROR)
    : Yaml::parseFile($input);
$spec = $document instanceof stdClass ? get_object_vars($document) : $document;
if (is_array($spec) && ($spec['paths'] ?? null) instanceof stdClass) {
    $spec['paths'] = get_object_vars($spec['paths']);
}

if (! is_array($spec) || ! isset($spec['paths']) || ! is_array($spec['paths'])) {
    fwrite(STDERR, "OpenAPI spec does not contain a paths object.\n");
    exit(1);
}

$prefixes = [
    '/api/client/extensions/'.$extension,
    '/api/admin/extensions/'.$extension,
    '/api/application/extensions/'.$extension,
    '/api/client/servers/{server}/extensions/'.$extension,
];

$paths = [];
foreach ($spec['paths'] as $path => $operations) {
    if (! is_string($path)) {
        continue;
    }

    foreach ($prefixes as $prefix) {
        if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
            $paths[$path] = $operations;

            break;
        }
    }
}

if ($paths === []) {
    fwrite(STDERR, "No extension OpenAPI paths found for {$extension}.\n");
    exit(1);
}

$spec['paths'] = $paths;
$spec['info'] = (array) ($spec['info'] ?? []);
$spec['info']['title'] = trim(($spec['info']['title'] ?? 'Pterodactyl API').' - '.$extension);
pruneUnusedSchemas($spec);

$directory = dirname($output);
if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
    fwrite(STDERR, "Unable to create output directory {$directory}.\n");
    exit(1);
}

$contents = $outputIsJson
    ? json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
    : Yaml::dump($spec, 20, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
if (file_put_contents($output, $contents) === false) {
    fwrite(STDERR, "Unable to write OpenAPI spec to {$output}.\n");
    exit(1);
}

printf("Extension OpenAPI written: %s (%d paths).\n", $output, count($paths));

/** @param array<string, mixed> $spec */
function pruneUnusedSchemas(array &$spec): void
{
    $components = $spec['components'] ?? null;
    $components = $components instanceof stdClass ? get_object_vars($components) : $components;
    if (! is_array($components) || ! isset($components['schemas'])) {
        return;
    }

    $schemas = $components['schemas'];
    $schemas = $schemas instanceof stdClass ? get_object_vars($schemas) : $schemas;
    if (! is_array($schemas)) {
        return;
    }

    $used = [];
    collectSchemaRefs($spec['paths'], $used);

    $queue = array_keys($used);
    while ($queue !== []) {
        $name = array_pop($queue);
        if (! is_string($name) || ! isset($schemas[$name])) {
            continue;
        }

        $before = array_keys($used);
        collectSchemaRefs($schemas[$name], $used);
        foreach (array_diff(array_keys($used), $before) as $next) {
            $queue[] = $next;
        }
    }

    $components['schemas'] = (object) array_intersect_key($schemas, $used);
    $spec['components'] = $components;
}

/** @param array<string, true> $used */
function collectSchemaRefs(mixed $value, array &$used): void
{
    $value = $value instanceof stdClass ? get_object_vars($value) : $value;
    if (! is_array($value)) {
        return;
    }

    $ref = $value['$ref'] ?? null;
    if (is_string($ref) && str_starts_with($ref, '#/components/schemas/')) {
        $used[mb_substr($ref, mb_strlen('#/components/schemas/'))] = true;
    }

    foreach ($value as $child) {
        collectSchemaRefs($child, $used);
    }
}
