<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Resources\Api\Includes\IncludeSetTest;

use Illuminate\Http\Request;
use League\Fractal\Manager;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;
use Pterodactyl\Http\Resources\Api\Includes\IncludeSet;
use Pterodactyl\Tests\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use UnexpectedValueException;

uses(TestCase::class);

/*
 * IncludeSet replaces league/fractal's include parsing, so it is fuzzed against Fractal's
 * Manager: random include strings built from include names, modifier syntax, empty segments
 * and whitespace. Digits are left out: Fractal compares numeric names such as "1" and "01"
 * loosely and IncludeSet strictly, which no real include name can tell apart.
 */

const FRAGMENTS = ['servers', 'user', 'nodes', 'a', 'b', 'limit(x|y)', ' ', '', 'x y', 'élan'];
const SEPARATORS = ['.', '.', ':', ',', '(', ')', '|', ' ', ''];

function randomInclude(Randomizer $random): string
{
    $include = '';
    for ($i = 0, $parts = $random->getInt(0, 6); $i < $parts; $i++) {
        $include .= FRAGMENTS[$random->getInt(0, count(FRAGMENTS) - 1)].SEPARATORS[$random->getInt(0, count(SEPARATORS) - 1)];
    }

    return $include;
}

/** @return list<string> */
function randomIncludes(Randomizer $random): array
{
    $includes = [];
    for ($i = 0, $count = $random->getInt(0, 6); $i < $count; $i++) {
        $includes[] = randomInclude($random);
    }

    return $includes;
}

/** @return list<string> */
function managerIncludes(string|array $includes, int $limit): array
{
    return (new Manager)->setRecursionLimit($limit)->parseIncludes($includes)->getRequestedIncludes();
}

/**
 * The Fractal container binding's request handling before IncludeSet took it over.
 *
 * @return list<string>
 */
function bindingNames(Request $request): array
{
    return $request->collect('include')
        ->map(fn (mixed $value): string => is_string($value) ? $value : throw new UnexpectedValueException('The requested includes must be strings.'))
        ->flatMap(fn (string $value): array => explode(',', $value))
        ->map(fn (string $value): string => mb_trim($value))
        ->filter()
        ->values()
        ->all();
}

test('include lists parse like the fractal manager', function (int $limit): void {
    $random = new Randomizer(new Mt19937($limit));

    for ($i = 0; $i < 1500; $i++) {
        $includes = randomIncludes($random);

        expect(IncludeSet::parse($includes, $limit)->includes())->toBe(managerIncludes($includes, $limit), json_encode($includes, JSON_THROW_ON_ERROR));
    }
})->with([1, 2, 3, 10]);

test('include strings parse like the fractal manager', function (int $limit): void {
    $random = new Randomizer(new Mt19937($limit + 100));

    for ($i = 0; $i < 1500; $i++) {
        $include = implode(',', randomIncludes($random));

        expect(IncludeSet::parse(explode(',', $include), $limit)->includes())->toBe(managerIncludes($include, $limit), $include);
    }
})->with([2, 10]);

test('requests parse like the fractal binding', function (string $source): void {
    $random = new Randomizer(new Mt19937(crc32($source)));

    for ($i = 0; $i < 500; $i++) {
        $value = $random->getInt(0, 1) === 0 ? implode(',', randomIncludes($random)) : randomIncludes($random);
        $forced = $random->getInt(0, 3) === 0 ? ['password'] : [];

        $request = match ($source) {
            'query' => Request::create('/?'.http_build_query(['include' => $value])),
            'body' => Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['include' => $value], JSON_THROW_ON_ERROR)),
        };
        $this->app->instance(Request::class, $request);

        $fractal = $this->app->make(Fractal::class)->item(['id' => 1], fn (array $data): array => $data);
        if ($forced !== []) {
            $fractal->parseIncludes($forced);
        }

        $includes = IncludeSet::fromRequest($request, $forced);
        $context = json_encode([$value, $forced], JSON_THROW_ON_ERROR);

        expect($includes->requestedNames())->toBe([...bindingNames($request), ...$forced], $context)
            ->and($includes->includes())->toBe($fractal->createData()->getManager()->getRequestedIncludes(), $context);
    }
})->with(['query', 'body']);

test('modifiers keep the include name and request their relation', function (): void {
    expect(IncludeSet::parse(['servers:limit(1|0).user', 'node:sort(id|desc)'], 2)->includes())->toBe(['servers', 'servers.user', 'node']);
});

test('a falsy modifier or relation requests nothing more, as in fractal', function (array $includes): void {
    expect(IncludeSet::parse($includes, 2)->includes())->toBe(managerIncludes($includes, 2));
})->with([
    'zero modifier' => [['servers:0.user']],
    'zero relation' => [['servers:limit.0']],
    'empty relation' => [['servers:limit.']],
    'empty modifier' => [['servers:.user']],
    'zero name' => [['0', '0:x.user']],
]);

test('numeric names are compared strictly', function (): void {
    expect(IncludeSet::parse(['1', '01'])->includes())->toBe(['1', '01'])
        ->and(managerIncludes(['1', '01'], 10))->toBe(['1']);
});

test('paths are cut to the recursion limit and parents are requested', function (): void {
    expect(IncludeSet::parse(['nodes.allocations.server', 'egg'], 2)->includes())->toBe(['nodes', 'nodes.allocations', 'egg'])
        ->and(IncludeSet::parse(['nodes.allocations.server'])->includes())->toBe(['nodes', 'nodes.allocations', 'nodes.allocations.server']);
});

test('forced includes follow the requested ones', function (): void {
    $includes = IncludeSet::fromRequest(Request::create('/?include=host,%20servers.user,,'), ['password']);

    expect($includes->requestedNames())->toBe(['host', 'servers.user', 'password'])
        ->and($includes->includes())->toBe(['host', 'servers', 'servers.user', 'password']);
});

test('scopes check the full path from the root', function (): void {
    $root = IncludeSet::parse(['servers.user', 'node'])->root();

    expect($root->isRequested('servers'))->toBeTrue()
        ->and($root->isRequested('user'))->toBeFalse()
        ->and($root->child('servers')->isRequested('user'))->toBeTrue()
        ->and($root->child('servers')->isRequested('node'))->toBeFalse()
        ->and($root->child('servers')->child('user')->isRequested('servers'))->toBeFalse()
        ->and($root->child('servers')->child('user')->depth())->toBe(2);
});
