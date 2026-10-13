<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Servers\CreateServerTest;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Pterodactyl\Contracts\Servers\CreatesServers;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonEmptyObject;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;
use UnexpectedValueException;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
uses(WithFaker::class);
/**
 * Stub the calls to Wings so that we don't actually hit those API endpoints.
 */
beforeEach(function () {
    /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
    Event::fake([OperationCompleted::class]);
    $this->bungeecord = Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail();
    $this->daemonServerRepository = new FakeDaemonServer;
    $this->owner = User::factory()->create();
    $this->allocation = Allocation::factory()->for(Node::factory()->for(Location::factory()))->create();
});
test('server is created with deployment object', function () {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var Location $location */
    $location = Location::factory()->create();
    /** @var Node $node */
    $node = Node::factory()->create(['location_id' => $location->id]);
    /** @var Allocation[]|Collection $allocations */
    $allocations = Allocation::factory()->times(5)->create(['node_id' => $node->id]);
    $deployment = (new DeploymentObject())->setDedicated(true)->setLocations([$node->location_id])->setPorts([$allocations[0]->port]);
    $egg = $this->cloneEggAndVariables($this->bungeecord);
    // We want to make sure that the validator service runs as an admin, and not as a regular
    // user when saving variables.
    $egg->variables()->first()->update(['user_editable' => false]);
    $data = ['name' => $this->faker->name, 'description' => $this->faker->sentence, 'owner_id' => $user->id, 'memory' => 256, 'swap' => 128, 'disk' => 100, 'io' => 500, 'cpu' => 0, 'startup' => 'java server2.jar', 'image' => 'java:8', 'egg_id' => $egg->id, 'allocation_additional' => [$allocations[4]->id], 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server2.jar'], 'start_on_completion' => true];
    try {
        getService()->create(array_merge($data, ['environment' => ['BUNGEE_VERSION' => '', 'SERVER_JARFILE' => 'server2.jar']]), $deployment);
        $this->fail('This execution pathway should not be reached.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveCount(1);
        expect($exception->errors())->toHaveKey('environment.BUNGEE_VERSION');
        expect($exception->errors()['environment.BUNGEE_VERSION'][0])->toBe('The Bungeecord Version variable field is required.');
    }
    $response = getService()->create($data, $deployment);
    $this->daemonServerRepository->assertCreatedWith(true);
    Event::assertDispatched(OperationCompleted::class, fn (OperationCompleted $event): bool => $event->serverUuid === $response->uuid && $event->operation === 'provision' && $event->successful);
    expect($response)->toBeInstanceOf(Server::class);
    expect($response->uuid)->not->toBeNull();
    expect(mb_substr($response->uuid, 0, 8))->toBe($response->uuidShort);
    expect($response->egg_id)->toBe($egg->id);
    expect($response->variables)->toHaveCount(2);
    expect($response->variables[0]->server_value)->toBe('123');
    expect($response->variables[1]->server_value)->toBe('server2.jar');
    foreach ($data as $key => $value) {
        if (in_array($key, ['allocation_additional', 'environment', 'start_on_completion'])) {
            continue;
        }
        expect($response->{$key})->toBe($value, "Failed asserting equality of '{$key}' in server response. Got: [{$response->{$key}}] Expected: [{$value}]");
    }
    expect($response->allocations)->toHaveCount(2);
    expect($response->allocations[0]->id)->toBe($response->allocation_id);
    expect($response->allocations[0]->id)->toBe($allocations[0]->id);
    expect($response->allocations[1]->id)->toBe($allocations[4]->id);
    expect($response->isSuspended())->toBeFalse();
    expect($response->oom_disabled)->toBeTrue();
    expect($response->database_limit)->toBe(0);
    expect($response->allocation_limit)->toBe(0);
    expect($response->backup_limit)->toBe(0);
});
test('error encountered by wings causes server to be deleted', function () {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var Location $location */
    $location = Location::factory()->create();
    /** @var Node $node */
    $node = Node::factory()->create(['location_id' => $location->id]);
    /** @var Allocation $allocation */
    $allocation = Allocation::factory()->create(['node_id' => $node->id]);
    $data = ['name' => $this->faker->name, 'description' => $this->faker->sentence, 'owner_id' => $user->id, 'allocation_id' => $allocation->id, 'node_id' => $allocation->node_id, 'memory' => 256, 'swap' => 128, 'disk' => 100, 'io' => 500, 'cpu' => 0, 'startup' => 'java server2.jar', 'image' => 'java:8', 'egg_id' => $this->bungeecord->id, 'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server2.jar']];
    $this->daemonServerRepository->throwable = new DaemonConnectionException(Http::failedRequest([], 500));
    try {
        getService()->create($data);
        $this->fail('Expected DaemonConnectionException to be thrown.');
    } catch (DaemonConnectionException) {
    }
    $this->daemonServerRepository->assertCreated();
    $this->daemonServerRepository->assertDeleted();
    Event::assertNotDispatched(OperationCompleted::class, fn (OperationCompleted $event): bool => $event->operation === 'provision');
    Event::assertDispatched(OperationCompleted::class, fn (OperationCompleted $event): bool => $event->operation === 'delete' && $event->successful);
    $this->assertDatabaseMissing('servers', ['owner_id' => $user->id]);
});
test('servers created without the http api meet the same rules', function (array $input, string $field) {
    try {
        getService()->create(directInput($this, $input));
        $this->fail('The server was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    $this->assertDatabaseMissing('servers', ['name' => 'Direct Server']);
    expect($this->daemonServerRepository->callsFor('create'))->toBe([]);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'a name that is too long' => [['name' => str_repeat('a', 192)], 'name'],
    'an owner id that is not an integer' => [['owner_id' => '10.5'], 'owner_id'],
    'an owner that does not exist' => [['owner_id' => 0], 'owner_id'],
    'an egg id that is not an integer' => [['egg_id' => '1.0'], 'egg_id'],
    'an egg that does not exist' => [['egg_id' => 0], 'egg_id'],
    'an image with shell characters' => [['image' => 'java;rm -rf /'], 'image'],
    'a missing startup command' => [['startup' => ''], 'startup'],
    'an environment that is not an object' => [['environment' => 'BUNGEE_VERSION=123'], 'environment'],
    'memory below zero' => [['memory' => -1], 'memory'],
    'swap below unlimited' => [['swap' => -2], 'swap'],
    'a fractional disk size' => [['disk' => 1024.5], 'disk'],
    'io outside its range' => [['io' => 5], 'io'],
    'cpu threads that are not a pattern' => [['threads' => '0-3;'], 'threads'],
    'an oom flag that is not a boolean' => [['oom_disabled' => 'yes'], 'oom_disabled'],
    'a skip scripts flag that is not a boolean' => [['skip_scripts' => 'yes'], 'skip_scripts'],
    'a negative database limit' => [['database_limit' => -1], 'database_limit'],
    'a missing allocation without a deployment' => [['allocation_id' => null], 'allocation_id'],
    'an allocation that does not exist' => [['allocation_id' => 0], 'allocation_id'],
    'additional allocations that are not a list' => [['allocation_additional' => 'all'], 'allocation_additional'],
    'a start flag that is not a boolean' => [['start_on_completion' => 'yes'], 'start_on_completion'],
]);
test('servers created without the http api only take allocations no server holds', function () {
    $held = $this->createServerModel()->allocation_id;
    $spare = Allocation::factory()->create(['node_id' => $this->allocation->node_id]);

    foreach ([
        'allocation_id' => ['allocation_id' => $held],
        'allocation_additional.1' => ['allocation_additional' => [$spare->id, $held]],
        'allocation_additional' => ['allocation_additional' => ['first' => $spare->id]],
    ] as $field => $input) {
        try {
            getService()->create(directInput($this, $input));
            $this->fail('The server was created.');
        } catch (ValidationException $validationException) {
            expect(array_keys($validationException->errors()))->toBe([$field]);
        }
    }

    expect($spare->refresh()->server_id)->toBeNull()
        ->and($this->daemonServerRepository->callsFor('create'))->toBe([]);
});
test('values are coerced the way laravel validates them', function (int|string $flag, bool $expected) {
    $spare = Allocation::factory()->create(['node_id' => $this->allocation->node_id]);

    $server = getService()->create(directInput($this, [
        'owner_id' => (string) $this->owner->id,
        'egg_id' => (string) $this->bungeecord->id,
        'memory' => '512',
        'swap' => '-1',
        'disk' => '1024',
        'io' => '500',
        'cpu' => '100',
        'skip_scripts' => $flag,
        'oom_disabled' => $flag,
        'start_on_completion' => $flag,
        'database_limit' => '1',
        'allocation_limit' => 2,
        'backup_limit' => '3',
        'allocation_id' => (string) $this->allocation->id,
        'allocation_additional' => [(string) $spare->id],
    ]));

    expect($server->owner_id)->toBe($this->owner->id)
        ->and($server->egg_id)->toBe($this->bungeecord->id)
        ->and($server->memory)->toBe(512)
        ->and($server->swap)->toBe(-1)
        ->and($server->disk)->toBe(1024)
        ->and($server->io)->toBe(500)
        ->and($server->cpu)->toBe(100)
        ->and($server->skip_scripts)->toBe($expected)
        ->and($server->oom_disabled)->toBe($expected)
        ->and($server->database_limit)->toBe(1)
        ->and($server->allocation_limit)->toBe(2)
        ->and($server->backup_limit)->toBe(3)
        ->and($server->allocation_id)->toBe($this->allocation->id)
        ->and($spare->refresh()->server_id)->toBe($server->id);
    $this->daemonServerRepository->assertCreatedWith($expected);
})->with([[1, true], ['1', true], [0, false], ['0', false]]);
test('a server given only the required fields takes the defaults', function () {
    $server = getService()->create(directInput($this, ['memory' => 0, 'disk' => 0, 'cpu' => 0, 'swap' => -1]));

    expect($server->external_id)->toBeNull()
        ->and($server->description)->toBe('')
        ->and($server->memory)->toBe(0)
        ->and($server->disk)->toBe(0)
        ->and($server->cpu)->toBe(0)
        ->and($server->swap)->toBe(-1)
        ->and($server->threads)->toBeNull()
        ->and($server->skip_scripts)->toBeFalse()
        ->and($server->oom_disabled)->toBeTrue()
        ->and($server->database_limit)->toBe(0)
        ->and($server->allocation_limit)->toBe(0)
        ->and($server->backup_limit)->toBe(0)
        ->and($server->node_id)->toBe($this->allocation->node_id)
        ->and($server->allocations->pluck('id')->all())->toBe([$this->allocation->id]);
    $this->daemonServerRepository->assertCreatedWith(false);
    Event::assertDispatched(OperationCompleted::class, fn (OperationCompleted $event): bool => $event->serverUuid === $server->uuid && $event->operation === 'provision');
});
test('null limits and a null oom flag take the defaults', function () {
    $server = getService()->create(directInput($this, ['oom_disabled' => null, 'database_limit' => null, 'allocation_limit' => null, 'backup_limit' => null, 'threads' => null]));

    expect($server->oom_disabled)->toBeTrue()
        ->and($server->database_limit)->toBe(0)
        ->and($server->allocation_limit)->toBe(0)
        ->and($server->backup_limit)->toBe(0)
        ->and($server->threads)->toBeNull();
});
test('cpu threads are a pattern that a bare number also matches', function (int|string $threads, string $expected) {
    expect(getService()->create(directInput($this, ['threads' => $threads]))->threads)->toBe($expected);
})->with([[0, '0'], [3, '3'], ['0-3', '0-3'], ['0,2,4-5', '0,2,4-5']]);
test('environment values reach the egg variable rules as they were sent', function () {
    $egg = $this->cloneEggAndVariables($this->bungeecord);
    $egg->variables()->where('env_variable', 'SERVER_JARFILE')->update(['rules' => 'required|string']);

    try {
        getService()->create(directInput($this, ['egg_id' => $egg->id, 'environment' => ['BUNGEE_VERSION' => 123, 'SERVER_JARFILE' => 25565]]));
        $this->fail('The server was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe(['environment.SERVER_JARFILE']);
    }

    $server = getService()->create(directInput($this, ['egg_id' => $egg->id, 'environment' => ['BUNGEE_VERSION' => 123, 'SERVER_JARFILE' => 'server.jar']]));

    expect($server->variables->pluck('server_value', 'env_variable')->all())->toBe(['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar']);
});
test('environment values must be scalars', function (array $environment) {
    expect(fn () => getService()->create(directInput($this, ['environment' => $environment])))->toThrow(UnexpectedValueException::class);

    $this->assertDatabaseMissing('servers', ['name' => 'Direct Server']);
})->with([
    'a nested value' => [['BUNGEE_VERSION' => ['nested'], 'SERVER_JARFILE' => 'server.jar']],
    'an empty object' => [['BUNGEE_VERSION' => new JsonEmptyObject, 'SERVER_JARFILE' => 'server.jar']],
]);
test('extension values must come from a request that validated them', function () {
    expect(fn () => getService()->create(directInput($this, ['extensions' => ['billing' => ['plan' => 'pro']]])))
        ->toThrow(InvalidArgumentException::class, 'must come from a request that validated them');

    $this->assertDatabaseMissing('servers', ['name' => 'Direct Server']);
});
test('a deployment picks the allocation, so none is needed', function () {
    $deployment = (new DeploymentObject())->setDedicated(false)->setLocations([$this->allocation->node->location_id])->setPorts([]);

    $server = getService()->create(directInput($this, ['allocation_id' => null]), $deployment);

    expect($server->allocation_id)->toBe($this->allocation->id)
        ->and($server->node_id)->toBe($this->allocation->node_id);
});
/** @return array<string, mixed> */
function directInput(IntegrationTestCase $test, array $overrides = []): array
{
    return [...[
        'name' => 'Direct Server',
        'owner_id' => $test->owner->id,
        'egg_id' => $test->bungeecord->id,
        'image' => 'ghcr.io/pterodactyl/yolks:java_21',
        'startup' => 'java -jar server.jar',
        'environment' => ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar'],
        'memory' => 256,
        'swap' => 0,
        'disk' => 100,
        'io' => 500,
        'cpu' => 0,
        'allocation_id' => $test->allocation->id,
    ], ...$overrides];
}
function getService(): CreatesServers
{
    return (function () {
        return $this->app->make(CreatesServers::class);
    })->call(pterodactylTestCase());
}
