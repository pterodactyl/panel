<?php

namespace Tests\Unit\Commands\Server;

use Mockery as m;
use Pterodactyl\Models\Node;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Illuminate\Validation\Factory;
use Tests\Unit\Commands\CommandTestCase;
use Pterodactyl\Console\Commands\Server\BulkPowerActionCommand;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface;

class BulkPowerActionCommandTest extends CommandTestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface|\Mockery\Mock
     */
    private $powerRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->powerRepository = m::mock(PowerRepositoryInterface::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
    }

    /**
     * Test that an action can be sent to all servers.
     */
    public function testSendAction()
    {
        /** @var \Pterodactyl\Models\Server[] $servers */
        $servers = factory(Server::class)->times(2)->make();

        foreach ($servers as &$server) {
            $server->setRelation('node', factory(Node::class)->make());
        }

        $this->repository->shouldReceive('getServersForPowerActionCount')
            ->once()
            ->with([], [])
            ->andReturn(2);

        $this->repository->shouldReceive('getServersForPowerAction')
            ->once()
            ->with([], [])
            ->andReturn($servers);

        for ($i = 0; $i < count($servers); $i++) {
            $this->powerRepository->shouldReceive('setNode->setServer->sendSignal')
                ->once()
                ->with('kill')
                ->andReturnNull();
        }

        $display = $this->runCommand($this->getCommand(), ['action' => 'kill'], ['yes']);

        $this->assertNotEmpty($display);
        $this->assertContains('2/2', $display);
        $this->assertContains(trans('command/messages.server.power.confirm', ['action' => 'kill', 'count' => 2]), $display);
    }

    /**
     * Test filtering servers and nodes.
     */
    public function testSendWithFilters()
    {
        $server = factory(Server::class)->make();
        $server->setRelation('node', $node = factory(Node::class)->make());

        $this->repository->shouldReceive('getServersForPowerActionCount')
            ->once()
            ->with([1, 2], [3, 4])
            ->andReturn(1);

        $this->repository->shouldReceive('getServersForPowerAction')
            ->once()
            ->with([1, 2], [3, 4])
            ->andReturn([$server]);

        $this->powerRepository->expects('setNode')->with($node)->andReturnSelf();
        $this->powerRepository->expects('setServer')->with($server)->andReturnSelf();
        $this->powerRepository->expects('sendSignal')->with('kill')->andReturn(new Response);

        $display = $this->runCommand($this->getCommand(), [
            'action' => 'kill',
            '--servers' => '1,2',
            '--nodes' => '3,4',
        ], ['yes']);

        $this->assertNotEmpty($display);
        $this->assertContains('1/1', $display);
        $this->assertContains(trans('command/messages.server.power.confirm', ['action' => 'kill', 'count' => 1]), $display);
    }

    /**
     * Test that sending empty options returns the expected results.
     */
    public function testSendWithEmptyOptions()
    {
        $server = factory(Server::class)->make();
        $server->setRelation('node', factory(Node::class)->make());

        $this->repository->shouldReceive('getServersForPowerActionCount')
            ->once()
            ->with([], [])
            ->andReturn(1);

        $this->repository->shouldReceive('getServersForPowerAction')->once()->with([], [])->andReturn([$server]);
        $this->powerRepository->shouldReceive('setNode->setServer->sendSignal')->once()->with('kill')->andReturnNull();

        $display = $this->runCommand($this->getCommand(), [
            'action' => 'kill',
            '--servers' => '',
            '--nodes' => '',
        ], ['yes']);

        $this->assertNotEmpty($display);
        $this->assertContains('1/1', $display);
        $this->assertContains(trans('command/messages.server.power.confirm', ['action' => 'kill', 'count' => 1]), $display);
    }

    /**
     * Test that validation occurs correctly.
     *
     * @param array $data
     *
     * @dataProvider validationFailureDataProvider
     * @expectedException \Illuminate\Validation\ValidationException
     */
    public function testValidationErrors(array $data)
    {
        $this->runCommand($this->getCommand(), $data);
    }

    /**
     * Provide invalid data for the command.
     *
     * @return array
     */
    public function validationFailureDataProvider(): array
    {
        return  [
            [['action' => 'hodor']],
            [['action' => 'hodor', '--servers' => 'hodor']],
            [['action' => 'kill', '--servers' => 'hodor']],
            [['action' => 'kill', '--servers' => '1,2,3', '--nodes' => 'hodor']],
            [['action' => 'kill', '--servers' => '1,2,3', '--nodes' => '1,2,test']],
        ];
    }

    /**
     * Return an instance of the command with mocked dependencies.
     *
     * @return \Pterodactyl\Console\Commands\Server\BulkPowerActionCommand
     */
    private function getCommand(): BulkPowerActionCommand
    {
        return new BulkPowerActionCommand($this->powerRepository, $this->repository, $this->app->make(Factory::class));
    }
}
