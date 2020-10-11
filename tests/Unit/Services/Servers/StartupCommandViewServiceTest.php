<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Servers\StartupCommandService;
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
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = m::mock(ServerRepositoryInterface::class);
    }

    /**
     * Test that the correct startup string is returned.
     */
    public function testServiceResponse()
    {

        $server = factory(Server::class)->make([
            'id' => 123,
            'startup' => 'example {{SERVER_MEMORY}} {{SERVER_IP}} {{SERVER_PORT}} {{TEST_VARIABLE}} {{TEST_VARIABLE_HIDDEN}} {{UNKNOWN}}',
        ]);

        $variables = collect([
            factory(EggVariable::class)->make([
                'env_variable' => 'TEST_VARIABLE',
                'server_value' => 'Test Value',
                'user_viewable' => 1,
            ]),
            factory(EggVariable::class)->make([
                'env_variable' => 'TEST_VARIABLE_HIDDEN',
                'server_value' => 'Hidden Value',
                'user_viewable' => 0,
            ]),
        ]);

        $server->setRelation('variables', $variables);
        $server->setRelation('allocation', $allocation = factory(Allocation::class)->make());

        $response = $this->getService()->handle($server);
        $this->assertSame(
            sprintf('example %s %s %s %s %s {{UNKNOWN}}', $server->memory, $allocation->ip, $allocation->port, 'Test Value', '[hidden]'),
            $response
        );
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\StartupCommandService
     */
    private function getService(): StartupCommandService
    {
        return new StartupCommandService;
    }
}
