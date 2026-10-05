<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Architecture\OpenApiContractTest;

use Generator;
use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Pterodactyl\Tests\Support\OpenApiSpecValidator;
use Pterodactyl\Tests\TestCase;
use Symfony\Component\Yaml\Yaml;

uses(TestCase::class);

/** @return array<string, mixed> */
function specification(): array
{
    static $spec = null;

    expect(OpenApiSpecValidator::specPath())->toBeFile('Generate the specification with composer docs:openapi first.');

    return $spec ??= Yaml::parseFile(OpenApiSpecValidator::specPath());
}

/** @return Generator<string, array{method: string, path: string, operation: array<string, mixed>, parameters: array<int, array<string, mixed>>}> */
function operations(): Generator
{
    foreach (specification()['paths'] as $path => $pathItem) {
        foreach (Arr::only($pathItem, ['get', 'post', 'put', 'patch', 'delete']) as $method => $operation) {
            yield Str::upper($method).' '.$path => [
                'method' => $method,
                'path' => $path,
                'operation' => $operation,
                'parameters' => [...($pathItem['parameters'] ?? []), ...($operation['parameters'] ?? [])],
            ];
        }
    }
}

function operationKey(string $method, string $uri): string
{
    return Str::upper($method).' /'.preg_replace('/\{[^}]+\}/', '{}', mb_trim($uri, '/'));
}

/**
 * @param  array<string, mixed>  $node
 * @return list<string>
 */
function schemaReferences(array $node): array
{
    return collect(Arr::dot($node))
        ->filter(fn (mixed $value, string $key): bool => ($key === '$ref' || Str::endsWith($key, '.$ref')) && is_string($value) && Str::startsWith($value, '#/components/schemas/'))
        ->map(fn (string $ref): string => Str::after($ref, '#/components/schemas/'))
        ->unique()->values()->all();
}

test('documents every registered API route and configured authentication route', function (): void {
    $prefixes = config('scribe.routes.0.match.prefixes');
    $expected = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RegisteredRoute $route): bool => Str::is($prefixes, $route->uri()))
        ->flatMap(fn (RegisteredRoute $route): array => array_map(
            fn (string $method): string => operationKey($method, $route->uri()),
            array_diff($route->methods(), ['HEAD', 'OPTIONS']),
        ))
        ->merge(array_map(function (string $route): string {
            [$method, $uri] = explode(' ', $route, 2);

            return operationKey($method, $uri);
        }, config('scribe.routes.0.include')))
        ->unique()->sort()->values()->all();
    $actual = collect(iterator_to_array(operations()))
        ->map(fn (array $entry): string => operationKey($entry['method'], $entry['path']))
        ->sort()->values()->all();

    expect($actual)->toBe($expected);
});

test('gives operations unique identifiers and useful documentation', function (): void {
    $identifiers = [];
    foreach (operations() as $label => $entry) {
        $operation = $entry['operation'];
        foreach (['operationId', 'summary', 'description'] as $field) {
            expect(mb_trim($operation[$field] ?? ''))->not->toBeEmpty($label.' '.$field);
        }

        $identifiers[] = $operation['operationId'];
    }

    expect($identifiers)->not->toBeEmpty()->toBe(array_values(array_unique($identifiers)));
});

test('documents path parameters and publishes reusable parameter schemas', function (): void {
    foreach (operations() as $label => $entry) {
        $parameters = collect($entry['parameters']);
        preg_match_all('/\{([^}?]+)\??\}/', $entry['path'], $matches);
        foreach ($matches[1] as $name) {
            $parameter = $parameters->last(fn (array $parameter): bool => ($parameter['in'] ?? null) === 'path' && ($parameter['name'] ?? null) === $name);
            expect($parameter)->not->toBeNull($label.' '.$name);
            expect(mb_trim($parameter['description'] ?? ''))->not->toBeEmpty($label.' '.$name);
        }

        foreach ($parameters->whereIn('in', ['path', 'query']) as $parameter) {
            expect($parameter['schema']['$ref'] ?? '')->toStartWith('#/components/schemas/', $label.' '.$parameter['name']);
        }
    }
});

test('publishes reusable request schemas and keeps GET inputs in parameters', function (): void {
    foreach (operations() as $label => $entry) {
        $operation = $entry['operation'];
        if ($entry['method'] === 'get') {
            expect($operation)->not->toHaveKey('requestBody', message: $label);
        }

        if (! isset($operation['requestBody'])) {
            continue;
        }

        $content = $operation['requestBody']['content'] ?? [];
        expect($content)->not->toBeEmpty($label);
        foreach ($content as $media) {
            expect($media['schema']['$ref'] ?? '')->toStartWith('#/components/schemas/', $label);
        }
    }
});

test('documents success responses and consistent reusable error envelopes', function (): void {
    foreach (operations() as $label => $entry) {
        $responses = $entry['operation']['responses'] ?? [];
        expect($responses)->not->toBeEmpty($label);
        expect(collect(array_keys($responses))->contains(fn (int|string $status): bool => Str::startsWith((string) $status, '2')))->toBeTrue($label.' success');
        expect(collect($responses)->contains(fn (array $response, int|string $status): bool => (string) $status === '204' || isset($response['content']['application/json']['schema'])))->toBeTrue($label.' schema');
        foreach ($responses as $status => $response) {
            foreach ($response['content'] ?? [] as $media) {
                $ref = $media['schema']['$ref'] ?? '';
                expect($ref)->toStartWith('#/components/schemas/', $label.' '.$status);
                if ((int) $status >= 400 && (int) $status < 600) {
                    expect($ref)->toBe('#/components/schemas/'.((int) $status === 422 ? 'ValidationErrorEnvelope' : 'ErrorEnvelope'), $label.' '.$status);
                }
            }
        }
    }
});

test('publishes concrete reachable component schemas without dangling references', function (): void {
    $spec = specification();
    $schemas = $spec['components']['schemas'];
    expect($schemas)->toHaveKeys(['ErrorEnvelope', 'ValidationErrorEnvelope', 'PaginationMeta']);
    foreach (Arr::dot($schemas) as $key => $value) {
        if ($value !== 'object' || ! Str::endsWith($key, '.type')) {
            continue;
        }

        $schema = Arr::get($schemas, Str::beforeLast($key, '.type'));
        expect(Arr::only($schema, ['properties', 'additionalProperties', 'oneOf', 'anyOf', 'allOf']))->not->toBeEmpty($key);
    }

    $pending = schemaReferences($spec['paths']);
    $reachable = [];
    while ($pending !== []) {
        $name = array_pop($pending);
        if (isset($reachable[$name])) {
            continue;
        }

        expect($schemas)->toHaveKey($name);
        $reachable[$name] = true;
        array_push($pending, ...schemaReferences($schemas[$name]));
    }

    expect(array_diff(array_keys($schemas), array_keys($reachable)))->toBe([]);
});
