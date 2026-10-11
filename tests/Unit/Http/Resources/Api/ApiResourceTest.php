<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Resources\Api\ApiResourceTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use LogicException;
use Pterodactyl\Http\Resources\Api\Admin\AdminResource;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Application\ApplicationResource;
use Pterodactyl\Http\Resources\Api\CallbackResource;
use Pterodactyl\Http\Resources\Api\Client\ClientResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Wings\ServerConfigurationCollection;
use Pterodactyl\Models\Location;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

/**
 * Emits `relationships` between its attributes, includes a callback by default, and
 * nests itself.
 *
 * @extends ApiResource<array<string, mixed>>
 */
final class TreeResource extends ApiResource
{
    protected array $defaultIncludes = ['tag'];

    protected array $availableIncludes = ['children', 'parent', 'hidden', 'absent'];

    public function resourceName(): string
    {
        return 'tree';
    }

    public function toArray(Request $request): array
    {
        return ['id' => $this->resource['id'], 'relationships' => [], 'name' => $this->resource['name']];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'tag' => $this->item($this->resource, fn (array $tree): array => ['label' => 'tag-'.$tree['id']]),
            'children' => $this->items($this->resource['children'] ?? [], self::class, 'tree'),
            'parent' => isset($this->resource['parent']) ? $this->item($this->resource['parent'], self::class, 'tree') : $this->null(),
            'hidden' => $this->null(),
            'absent' => null,
        };
    }
}

/**
 * Never emits `relationships`, so includes are appended after its attributes.
 *
 * @extends ApiResource<array<string, mixed>>
 */
final class FlatResource extends ApiResource
{
    protected array $availableIncludes = ['children'];

    public function resourceName(): string
    {
        return 'flat';
    }

    public function toArray(Request $request): array
    {
        return ['id' => $this->resource['id'], 'name' => $this->resource['name']];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return $this->items($this->resource['children'] ?? [], self::class);
    }
}

/**
 * @extends ApiResource<Location>
 */
final class LocationFixtureResource extends ApiResource
{
    protected array $availableIncludes = ['self'];

    public function resourceName(): string
    {
        return 'location';
    }

    public function toArray(Request $request): array
    {
        return ['id' => $this->resource->id, 'short' => $this->resource->short, ...$this->extensionFields($this->resource)];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return $this->item($this->resource, self::class, 'location');
    }
}

/** @extends ApplicationResource<array<string, mixed>> */
final class ApplicationFixtureResource extends ApplicationResource
{
    protected array $availableIncludes = ['admin'];

    public function resourceName(): string
    {
        return 'application';
    }

    public function toArray(Request $request): array
    {
        return ['id' => $this->resource['id']];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return $this->item($this->resource, AdminFixtureResource::class, 'admin');
    }
}

/** @extends AdminResource<array<string, mixed>> */
final class AdminFixtureResource extends AdminResource
{
    public function resourceName(): string
    {
        return 'admin';
    }

    public function toArray(Request $request): array
    {
        return ['id' => $this->resource['id']];
    }
}

/** @param ApiResource<*>|ApiResourceCollection $resource */
function render(ApiResource|ApiResourceCollection $resource, string $uri = '/'): string
{
    return json_encode($resource->resolve(Request::create($uri)), JSON_THROW_ON_ERROR);
}

/** @return array<string, mixed> */
function tree(int $id, string $name, array $children = [], ?array $parent = null): array
{
    return ['id' => $id, 'name' => $name, 'children' => $children, ...($parent === null ? [] : ['parent' => $parent])];
}

function location(): Location
{
    return (new Location)->forceFill(['id' => 7, 'short' => 'us-east']);
}

test('an item renders as an object with its attributes', function (): void {
    expect(render(new FlatResource(tree(1, 'one'))))->toBe('{"object":"flat","attributes":{"id":1,"name":"one"}}');
});

test('the controller can name the object, and an empty name renders null', function (?string $key): void {
    expect(render((new FlatResource(tree(1, 'one')))->withResourceKey($key)))->toBe('{"object":null,"attributes":{"id":1,"name":"one"}}');
})->with([null, '', '0']);

test('includes are merged into relationships where the attributes emit it', function (): void {
    $resource = new TreeResource(tree(1, 'root', [tree(2, 'child')], tree(0, 'up')));

    expect(render($resource, '/?include=parent,children,hidden,absent'))->toBe(
        '{"object":"tree","attributes":{"id":1,"relationships":{'
        .'"tag":{"object":null,"attributes":{"label":"tag-1"}},'
        .'"children":{"object":"list","data":[{"object":"tree","attributes":{"id":2,"relationships":{"tag":{"object":null,"attributes":{"label":"tag-2"}}},"name":"child"}}]},'
        .'"parent":{"object":"tree","attributes":{"id":0,"relationships":{"tag":{"object":null,"attributes":{"label":"tag-0"}}},"name":"up"}},'
        .'"hidden":{"object":"null_resource","attributes":null}'
        .'},"name":"root"}}',
    );
});

test('default includes come first, then requested includes in their declared order', function (): void {
    $resource = new TreeResource(tree(1, 'root', [], tree(0, 'up')));

    expect(array_keys(json_decode(render($resource, '/?include=hidden,parent,children'), true)['attributes']['relationships']))
        ->toBe(['tag', 'children', 'parent', 'hidden']);
});

test('relationships stay an empty list when nothing is included', function (): void {
    $resource = new LocationFixtureResource(location());

    expect(render($resource))->toBe('{"object":"location","attributes":{"id":7,"short":"us-east"}}')
        ->and(render(new TreeResource(tree(1, 'root'))))->toContain('"relationships":{"tag":');
});

test('includes are appended last when the attributes do not emit relationships', function (): void {
    $resource = (new LocationFixtureResource(location()))->withExtensionFields(read: false);

    expect(render($resource, '/?include=self'))->toBe(
        '{"object":"location","attributes":{"id":7,"short":"us-east","extensions":{},"relationships":{"self":{"object":"location","attributes":{"id":7,"short":"us-east"}}}}}',
    )->and(render(new FlatResource(tree(1, 'one', [tree(2, 'two')])), '/?include=children'))->toBe(
        '{"object":"flat","attributes":{"id":1,"name":"one","relationships":{"children":{"object":"list","data":[{"object":null,"attributes":{"id":2,"name":"two"}}]}}}}',
    );
});

test('extension fields render only on the root item', function (): void {
    $list = LocationFixtureResource::collection([location()]);

    expect(render((new LocationFixtureResource(location()))->withExtensionFields(read: false), '/?include=self'))->toContain('"short":"us-east","extensions":{},"relationships":{"self":{"object":"location","attributes":{"id":7,"short":"us-east"}}}')
        ->and(render($list))->toBe('{"object":"list","data":[{"object":"location","attributes":{"id":7,"short":"us-east"}}]}');
});

test('requested paths are cut to two levels while default includes apply at every depth', function (): void {
    $resource = new TreeResource(tree(1, 'a', [tree(2, 'b', [tree(3, 'c', [tree(4, 'd')])])]));

    $json = json_decode(render($resource, '/?include=children.children.children'), true);
    $level2 = $json['attributes']['relationships']['children']['data'][0]['attributes']['relationships']['children']['data'][0]['attributes'];

    expect(array_keys($level2['relationships']))->toBe(['tag'])
        ->and($level2['id'])->toBe(3);
});

test('forced includes render whatever the request asked for', function (): void {
    expect(render((new TreeResource(tree(1, 'root')))->withIncludes('hidden')))->toContain('"hidden":{"object":"null_resource","attributes":null}');
});

test('meta follows the object and keeps the first value of a key', function (): void {
    $resource = (new FlatResource(tree(1, 'one')))->withMeta(['resource' => 'first'])->withMeta(['resource' => 'second', 'token' => 'abc']);

    expect(render($resource))->toBe('{"object":"flat","attributes":{"id":1,"name":"one"},"meta":{"resource":"first","token":"abc"}}');
});

test('a list renders its items as a list under data and drops their keys', function (): void {
    expect(render(FlatResource::collection(['a' => tree(1, 'one'), 'b' => tree(2, 'two')])))
        ->toBe('{"object":"list","data":[{"object":"flat","attributes":{"id":1,"name":"one"}},{"object":"flat","attributes":{"id":2,"name":"two"}}]}')
        ->and(render(FlatResource::collection([])))->toBe('{"object":"list","data":[]}')
        ->and(render(FlatResource::collection([tree(1, 'one')])->withResourceKey('named')))->toBe('{"object":"list","data":[{"object":"named","attributes":{"id":1,"name":"one"}}]}');
});

test('a paginated list appends pagination after its other meta', function (): void {
    $paginator = new LengthAwarePaginator([tree(3, 'three'), tree(4, 'four')], 5, 2, 2, ['path' => 'http://localhost/api/trees']);
    $paginator->appends(['filter' => ['name' => 't']]);

    expect(render(FlatResource::collection($paginator)->withMeta(['resource' => 'x'])))->toBe(
        '{"object":"list","data":[{"object":"flat","attributes":{"id":3,"name":"three"}},{"object":"flat","attributes":{"id":4,"name":"four"}}],'
        .'"meta":{"resource":"x","pagination":{"total":5,"count":2,"per_page":2,"current_page":2,"total_pages":3,'
        .'"links":{"previous":"http:\/\/localhost\/api\/trees?filter%5Bname%5D=t&page=1","next":"http:\/\/localhost\/api\/trees?filter%5Bname%5D=t&page=3"}}}}',
    );
});

test('pagination links are an empty object on a single page and coerce counts to integers', function (): void {
    $paginator = new LengthAwarePaginator([tree(1, 'one')], '1', '50', '1', ['path' => 'http://localhost/api/trees']);

    expect(render(FlatResource::collection($paginator)))->toEndWith('"meta":{"pagination":{"total":1,"count":1,"per_page":50,"current_page":1,"total_pages":1,"links":{}}}}')
        ->and(render(FlatResource::collection(new LengthAwarePaginator([tree(1, 'one')], 3, 1, 1, ['path' => 'http://localhost/a']))))->toEndWith('"links":{"next":"http:\/\/localhost\/a?page=2"}}}}');
});

test('callback resources render the closure and are named only when given a name', function (): void {
    $callback = fn (array $tree): array => ['label' => $tree['name']];

    expect(render(new CallbackResource(tree(1, 'one'), $callback)))->toBe('{"object":null,"attributes":{"label":"one"}}')
        ->and(render(CallbackResource::list([tree(1, 'one')], $callback, 'database')))->toBe('{"object":"list","data":[{"object":"database","attributes":{"label":"one"}}]}')
        ->and(fn () => CallbackResource::collection([tree(1, 'one')]))->toThrow(LogicException::class);
});

test('responses answer 200 unless the controller asks for another status', function (): void {
    $location = location();
    $location->wasRecentlyCreated = true;
    $request = Request::create('/');
    $this->app->instance('request', $request);

    $response = (new LocationFixtureResource($location))->toResponse($request);
    $created = (new LocationFixtureResource($location))->respond(201);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('{"object":"location","attributes":{"id":7,"short":"us-east"}}')
        ->and($response->getEncodingOptions())->toBe(0)
        ->and($created->getStatusCode())->toBe(201)
        ->and($created->getContent())->toBe($response->getContent())
        ->and(FlatResource::collection([tree(1, 'a/b')])->toResponse($request)->getContent())->toBe('{"object":"list","data":[{"object":"flat","attributes":{"id":1,"name":"a\/b"}}]}');
});

test('resources nest only resources of their own api', function (): void {
    expect(fn () => render(new ApplicationFixtureResource(['id' => 1]), '/?include=admin'))
        ->toThrow(\Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException::class);
});

test('api resources never wrap their response while wings resources keep laravel wrapping', function (): void {
    expect(ApiResource::$wrap)->toBeNull()
        ->and(ApiResourceCollection::$wrap)->toBeNull()
        ->and(ApplicationResource::$wrap)->toBeNull()
        ->and(AdminResource::$wrap)->toBeNull()
        ->and(ClientResource::$wrap)->toBeNull()
        ->and(ServerConfigurationCollection::$wrap)->toBe('data')
        ->and(JsonResource::$wrap)->toBe('data');
});
