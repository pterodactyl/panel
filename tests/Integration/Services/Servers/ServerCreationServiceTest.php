<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Servers\ServerCreationServiceTest;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
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
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

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
function getService(): CreatesServers
{
    return (function () {
        return $this->app->make(CreatesServers::class);
    })->call(pterodactylTestCase());
}
