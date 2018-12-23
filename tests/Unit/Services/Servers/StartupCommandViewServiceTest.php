<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Servers\StartupCommandViewService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class StartupCommandViewServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(ServerRepositoryInterface::class);
    }

    /**
     * Test that the correct startup string is returned.
     */
    public function testServiceResponse()
    {
        $allocation = factory(Allocation::class)->make();
        $egg = factory(Egg::class)->make();
        $server = factory(Server::class)->make([
            'startup' => 'example {{SERVER_MEMORY}} {{SERVER_IP}} {{SERVER_PORT}} {{TEST_VARIABLE}} {{TEST_VARIABLE_HIDDEN}} {{UNKNOWN}}',
        ]);

        $variables = collect([
            factory(EggVariable::class)->make(['env_variable' => 'TEST_VARIABLE', 'user_viewable' => 1]),
            factory(EggVariable::class)->make(['env_variable' => 'TEST_VARIABLE_HIDDEN', 'user_viewable' => 0]),
        ]);

        $egg->setRelation('variables', $variables);
        $server->setRelation('allocation', $allocation);
        $server->setRelation('egg', $egg);

        $this->repository->shouldReceive('getVariablesWithValues')->once()->with($server->id, true)->andReturn((object) [
            'data' => [
                'TEST_VARIABLE' => 'Test Value',
                'TEST_VARIABLE_HIDDEN' => 'Hidden Value',
            ],
            'server' => $server,
        ]);

        $this->repository->shouldReceive('getPrimaryAllocation')->once()->with($server)->andReturn($server);

        $response = $this->getService()->handle($server->id);
        $this->assertInstanceOf(Collection::class, $response);

        $this->assertSame(
            sprintf('example %s %s %s %s %s {{UNKNOWN}}', $server->memory, $allocation->ip, $allocation->port, 'Test Value', '[hidden]'),
            $response->get('startup')
        );
        $this->assertEquals($variables->only(0), $response->get('variables'));
        $this->assertSame([
            'TEST_VARIABLE' => 'Test Value',
            'TEST_VARIABLE_HIDDEN' => 'Hidden Value',
        ], $response->get('server_values'));
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\StartupCommandViewService
     */
    private function getService(): StartupCommandViewService
    {
        return new StartupCommandViewService($this->repository);
    }
}
