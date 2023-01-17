<?php

namespace Pterodactyl\Tests\Integration\Services\Servers;

use Mockery\MockInterface;
use Pterodactyl\Models\Egg;
use GuzzleHttp\Psr7\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Allocation;
use Illuminate\Foundation\Testing\WithFaker;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class ServerCreationServiceTest extends IntegrationTestCase
{
    use WithFaker;

    protected MockInterface $daemonServerRepository;

    protected Egg $bungeecord;

    /**
     * Stub the calls to Wings so that we don't actually hit those API endpoints.
     */
    public function setUp(): void
    {
        parent::setUp();

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->bungeecord = Egg::query()
            ->where('author', 'support@pterodactyl.io')
            ->where('name', 'Bungeecord')
            ->firstOrFail();

        $this->daemonServerRepository = \Mockery::mock(DaemonServerRepository::class);
        $this->swap(DaemonServerRepository::class, $this->daemonServerRepository);
    }

    /**
     * Test that a server can be created when a deployment object is provided to the service.
     *
     * This doesn't really do anything super complicated, we'll rely on other more specific
     * tests to cover that the logic being used does indeed find suitable nodes and ports. For
     * this test we just care that it is recognized and passed off to those functions.
     */
    public function testServerIsCreatedWithDeploymentObject()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();

        /** @var \Pterodactyl\Models\Location $location */
        $location = Location::factory()->create();

        /** @var \Pterodactyl\Models\Node $node */
        $node = Node::factory()->create([
            'location_id' => $location->id,
        ]);

        /** @var \Pterodactyl\Models\Allocation[]|\Illuminate\Database\Eloquent\Collection $allocations */
        $allocations = Allocation::factory()->times(5)->create([
            'node_id' => $node->id,
        ]);

        $deployment = (new DeploymentObject())->setDedicated(true)->setLocations([$node->location_id])->setPorts([
            $allocations[0]->port,
        ]);

        $egg = $this->cloneEggAndVariables($this->bungeecord);
        // We want to make sure that the validator service runs as an admin, and not as a regular
        // user when saving variables.
        $egg->variables()->first()->update([
            'user_editable' => false,
        ]);

        $data = [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'owner_id' => $user->id,
            'memory' => 256,
            'swap' => 128,
            'disk' => 100,
            'io' => 500,
            'cpu' => 0,
            'startup' => 'java server2.jar',
            'image' => 'java:8',
            'egg_id' => $egg->id,
            'allocation_additional' => [
                $allocations[4]->id,
            ],
            'environment' => [
                'BUNGEE_VERSION' => '123',
                'SERVER_JARFILE' => 'server2.jar',
            ],
            'start_on_completion' => true,
        ];

        $this->daemonServerRepository->expects('setServer->create')->with(true)->andReturnUndefined();

        try {
            $this->getService()->handle(array_merge($data, [
                'environment' => [
                    'BUNGEE_VERSION' => '',
                    'SERVER_JARFILE' => 'server2.jar',
                ],
            ]), $deployment);

            $this->fail('This execution pathway should not be reached.');
        } catch (ValidationException $exception) {
            $this->assertCount(1, $exception->errors());
            $this->assertArrayHasKey('environment.BUNGEE_VERSION', $exception->errors());
            $this->assertSame('The Bungeecord Version variable field is required.', $exception->errors()['environment.BUNGEE_VERSION'][0]);
        }

        $response = $this->getService()->handle($data, $deployment);

        $this->assertInstanceOf(Server::class, $response);
        $this->assertNotNull($response->uuid);
        $this->assertSame($response->uuidShort, substr($response->uuid, 0, 8));
        $this->assertSame($egg->id, $response->egg_id);
        $variables = $response->variables->sortBy('server_value')->values();
        $this->assertCount(2, $variables);
        $this->assertSame('123', $variables->get(0)->server_value);
        $this->assertSame('server2.jar', $variables->get(1)->server_value);

        foreach ($data as $key => $value) {
            if (in_array($key, ['allocation_additional', 'environment', 'start_on_completion'])) {
                continue;
            }

            $this->assertSame($value, $response->{$key}, "Failed asserting equality of '$key' in server response. Got: [{$response->{$key}}] Expected: [$value]");
        }

        $this->assertCount(2, $response->allocations);
        $this->assertSame($response->allocation_id, $response->allocations[0]->id);
        $this->assertSame($allocations[0]->id, $response->allocations[0]->id);
        $this->assertSame($allocations[4]->id, $response->allocations[1]->id);

        $this->assertFalse($response->isSuspended());
        $this->assertFalse($response->oom_killer);
        $this->assertSame(0, $response->database_limit);
        $this->assertSame(0, $response->allocation_limit);
        $this->assertSame(0, $response->backup_limit);
    }

    /**
     * Test that a server is deleted from the Panel if Wings returns an error during the creation
     * process.
     */
    public function testErrorEncounteredByWingsCausesServerToBeDeleted()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();

        /** @var \Pterodactyl\Models\Location $location */
        $location = Location::factory()->create();

        /** @var \Pterodactyl\Models\Node $node */
        $node = Node::factory()->create([
            'location_id' => $location->id,
        ]);

        /** @var \Pterodactyl\Models\Allocation $allocation */
        $allocation = Allocation::factory()->create([
            'node_id' => $node->id,
        ]);

        $data = [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'owner_id' => $user->id,
            'allocation_id' => $allocation->id,
            'node_id' => $allocation->node_id,
            'memory' => 256,
            'swap' => 128,
            'disk' => 100,
            'io' => 500,
            'cpu' => 0,
            'startup' => 'java server2.jar',
            'image' => 'java:8',
            'egg_id' => $this->bungeecord->id,
            'environment' => [
                'BUNGEE_VERSION' => '123',
                'SERVER_JARFILE' => 'server2.jar',
            ],
        ];

        $this->daemonServerRepository->expects('setServer->create')->andThrows(
            new DaemonConnectionException(
                new BadResponseException('Bad request', new Request('POST', '/create'), new Response(500))
            )
        );

        $this->daemonServerRepository->expects('setServer->delete')->andReturnUndefined();

        $this->expectException(DaemonConnectionException::class);

        $this->getService()->handle($data);

        $this->assertDatabaseMissing('servers', ['owner_id' => $user->id]);
    }

    private function getService(): ServerCreationService
    {
        return $this->app->make(ServerCreationService::class);
    }
}
