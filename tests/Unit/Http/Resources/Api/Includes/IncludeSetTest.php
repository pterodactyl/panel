<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Resources\Api\Includes\IncludeSetTest;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\Includes\IncludeSet;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

test('include lists parse to every requested path', function (array $includes, array $limitedToTwo, array $limitedToTen): void {
    expect(IncludeSet::parse($includes, 2)->includes())->toBe($limitedToTwo)
        ->and(IncludeSet::parse($includes)->includes())->toBe($limitedToTen);
})->with([
    'modifiers keep the name' => [
        ['servers:limit(1|0)', 'node:sort(id|desc)'],
        ['servers', 'node'],
        ['servers', 'node'],
    ],
    'a modifier relation is requested' => [
        ['servers:limit(1|0).user', 'node:sort(id|desc)'],
        ['servers', 'servers.user', 'node'],
        ['servers', 'servers.user', 'node'],
    ],
    'a modifier relation is cut to the limit' => [
        ['servers:limit(1).user.node'],
        ['servers', 'servers.user'],
        ['servers', 'servers.user', 'servers.user.node'],
    ],
    'paths are cut to the limit' => [
        ['nodes.allocations.server.egg', 'egg'],
        ['nodes', 'nodes.allocations', 'egg'],
        ['nodes', 'nodes.allocations', 'nodes.allocations.server', 'nodes.allocations.server.egg', 'egg'],
    ],
    'deep paths stop at ten segments' => [
        ['a.b.c.d.e.f.g.h.i.j.k.l'],
        ['a', 'a.b'],
        ['a', 'a.b', 'a.b.c', 'a.b.c.d', 'a.b.c.d.e', 'a.b.c.d.e.f', 'a.b.c.d.e.f.g', 'a.b.c.d.e.f.g.h', 'a.b.c.d.e.f.g.h.i', 'a.b.c.d.e.f.g.h.i.j'],
    ],
    'parents are requested' => [
        ['servers.user.node', 'egg.nest'],
        ['servers', 'servers.user', 'egg', 'egg.nest'],
        ['servers', 'servers.user', 'servers.user.node', 'egg', 'egg.nest'],
    ],
    'duplicates are dropped' => [
        ['user', 'servers.user', 'user', 'servers', 'servers.user:limit(1)', 'node:x', 'node'],
        ['user', 'servers', 'servers.user', 'node'],
        ['user', 'servers', 'servers.user', 'node'],
    ],
    'paths that match once cut are dropped' => [
        ['nodes.allocations.server', 'nodes.allocations.node'],
        ['nodes', 'nodes.allocations'],
        ['nodes', 'nodes.allocations', 'nodes.allocations.server', 'nodes.allocations.node'],
    ],
    'names are not trimmed' => [
        [' servers', 'servers ', 'user . node', ''],
        [' servers', 'servers ', 'user ', 'user . node', ''],
        [' servers', 'servers ', 'user ', 'user . node', ''],
    ],
]);

test('a falsy modifier or relation requests nothing more', function (array $includes, array $expected): void {
    expect(IncludeSet::parse($includes, 2)->includes())->toBe($expected);
})->with([
    'zero modifier' => [['servers:0.user'], ['servers', 'servers.user']],
    'zero relation' => [['servers:limit.0'], ['servers']],
    'empty relation' => [['servers:limit.'], ['servers']],
    'empty modifier' => [['servers:.user'], ['servers', 'servers.user']],
    'zero name' => [['0', '0:x.user'], ['0']],
]);

test('numeric names are compared strictly', function (): void {
    expect(IncludeSet::parse(['1', '01'])->includes())->toBe(['1', '01']);
});

test('requests are read from the query string and the body', function (Request $request, array $forced, array $names, array $includes): void {
    $set = IncludeSet::fromRequest($request, $forced);

    expect($set->requestedNames())->toBe($names)
        ->and($set->includes())->toBe($includes);
})->with([
    'a query string, with forced includes after it' => [
        Request::create('/?include=host,%20servers.user,,'),
        ['password'],
        ['host', 'servers.user', 'password'],
        ['host', 'servers', 'servers.user', 'password'],
    ],
    'a query list' => [
        Request::create('/?include[]=host&include[]=servers.user.node,%20egg'),
        [],
        ['host', 'servers.user.node', 'egg'],
        ['host', 'servers', 'servers.user', 'egg'],
    ],
    'a json string' => [
        Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"include":"host, servers:limit(1).user"}'),
        [],
        ['host', 'servers:limit(1).user'],
        ['host', 'servers', 'servers.user'],
    ],
    'a json list, with forced includes after it' => [
        Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"include":["host","servers.user,egg"]}'),
        ['password'],
        ['host', 'servers.user', 'egg', 'password'],
        ['host', 'servers', 'servers.user', 'egg', 'password'],
    ],
    'a form body' => [
        Request::create('/', 'POST', ['include' => 'egg,node']),
        [],
        ['egg', 'node'],
        ['egg', 'node'],
    ],
    'the body over the query string' => [
        Request::create('/?include=node', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"include":"egg"}'),
        [],
        ['egg'],
        ['egg'],
    ],
    'only forced includes' => [
        Request::create('/'),
        ['password'],
        ['password'],
        ['password'],
    ],
]);

test('scopes check the full path from the root', function (): void {
    $root = IncludeSet::parse(['servers.user', 'node'])->root();

    expect($root->isRequested('servers'))->toBeTrue()
        ->and($root->isRequested('user'))->toBeFalse()
        ->and($root->child('servers')->isRequested('user'))->toBeTrue()
        ->and($root->child('servers')->isRequested('node'))->toBeFalse()
        ->and($root->child('servers')->child('user')->isRequested('servers'))->toBeFalse()
        ->and($root->child('servers')->child('user')->depth())->toBe(2);
});
