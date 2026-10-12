<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Architecture\OpenApiBaselineTest;

use Pterodactyl\Tests\Support\OpenApiSpecValidator;
use Pterodactyl\Tests\TestCase;
use stdClass;
use Symfony\Component\Yaml\Yaml;

/*
 * Pins the generated OpenAPI document to a committed baseline, so changing how responses are
 * produced cannot change the documented contract without a visible diff. Regenerate the specification and the clients first, then run this test:
 *
 *     npm run api:refresh && npm run sdk:generate
 *     composer docs:openapi:baseline
 *
 * The TypeScript generated from the specification is committed, so the other half of the
 * check is that the regeneration above leaves it untouched:
 *
 *     git diff --exit-code -- resources/scripts/api/generated packages/sdk/types
 *
 * Two parts of the document are left out of the comparison because they change on every
 * generation: example values (Scribe builds them from factory models with random attributes
 * and the time of generation) and the server URL (APP_URL). Everything else, every path,
 * parameter, schema, type, nullable flag, enum and description, must match byte for byte.
 *
 * A deliberate documentation change is accepted by rewriting the baseline from the
 * regenerated specification:
 *
 *     OPENAPI_BASELINE_UPDATE=1 composer docs:openapi:baseline
 */

uses(TestCase::class);

/**
 * Drop every example from the document. Maps are parsed as objects so an empty map and an
 * empty list stay distinct, and a field that is itself called "example" is a key of a
 * schema's properties rather than an example, so it is kept.
 */
function withoutExamples(mixed $node, bool $inProperties = false): mixed
{
    if (is_array($node)) {
        return array_map(fn (mixed $value): mixed => withoutExamples($value), $node);
    }

    if (! $node instanceof stdClass) {
        return $node;
    }

    $result = new stdClass;
    foreach (get_object_vars($node) as $key => $value) {
        if (! $inProperties && in_array($key, ['example', 'examples'], true)) {
            continue;
        }

        $result->{$key} = withoutExamples($value, ! $inProperties && $key === 'properties');
    }

    return $result;
}

function normalizedSpecification(string $path): string
{
    $specification = Yaml::parseFile($path, Yaml::PARSE_OBJECT_FOR_MAP);
    expect($specification)->toBeInstanceOf(stdClass::class);
    unset($specification->servers);

    return Yaml::dump(withoutExamples($specification), 64, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
}

test('the generated OpenAPI specification matches the committed baseline', function (): void {
    $generated = OpenApiSpecValidator::specPath();
    $baseline = base_path('tests/Fixtures/openapi/baseline.yaml');

    expect($generated)->toBeFile('Generate the specification with npm run api:refresh first.');

    if (env('OPENAPI_BASELINE_UPDATE')) {
        file_put_contents($baseline, normalizedSpecification($generated));
    }

    expect($baseline)->toBeFile()
        ->and(normalizedSpecification($generated))->toBe(file_get_contents($baseline), 'The generated OpenAPI specification differs from tests/Fixtures/openapi/baseline.yaml.');
});
