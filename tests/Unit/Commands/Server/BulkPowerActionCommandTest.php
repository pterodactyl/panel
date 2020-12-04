<?php

namespace Tests\Unit\Commands\Server;

use Mockery as m;
use Pterodactyl\Models\Node;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Validation\Factory;
use Tests\Unit\Commands\CommandTestCase;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Console\Commands\Server\BulkPowerActionCommand;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class BulkPowerActionCommandTest extends CommandTestCase
{
    /**
     * @var \Mockery\MockInterface
     */
    private $powerRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $repository;

    /**
     * Setup test.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->powerRepository = m::mock(DaemonPowerRepository::class);
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

        $this->repository->expects('getServersForPowerActionCount')->with([], [])->andReturn(2);
        $this->repository->expects('getServersForPowerAction')->with([], [])->andReturn($servers);

        for ($i = 0; $i < count($servers); $i++) {
            $this->powerRepository->expects('setServer->send')->with('kill')->andReturnNull();
        }

        $display = $this->runCommand($this->getCommand(), ['action' => 'kill'], ['yes']);

        $this->assertNotEmpty($display);
        $this->assertStringContainsString('2/2', $display);
        $this->assertStringContainsString(trans('command/messages.server.power.confirm', ['action' => 'kill', 'count' => 2]), $display);
    }

    /**
     * Test filtering servers and nodes.
     */
    public function testSendWithFilters()
    {
        $server = factory(Server::class)->make();
        $server->setRelation('node', $node = factory(Node::class)->make());

        $this->repository->expects('getServersForPowerActionCount')
            ->with([1, 2], [3, 4])
            ->andReturn(1);

        $this->repository->expects('getServersForPowerAction')
            ->with([1, 2], [3, 4])
            ->andReturn(Collection::make([$server]));

        $this->powerRepository->expects('setNode')->with($node)->andReturnSelf();
        $this->powerRepository->expects('setServer')->with($server)->andReturnSelf();
        $this->powerRepository->expects('send')->with('kill')->andReturn(new Response);

        $display = $this->runCommand($this->getCommand(), [
            'action' => 'kill',
            '--servers' => '1,2',
            '--nodes' => '3,4',
        ], ['yes']);

        $this->assertNotEmpty($display);
        $this->assertStringContainsString('1/1', $display);
        $this->assertStringContainsString(trans('command/messages.server.power.confirm', ['action' => 'kill', 'count' => 1]), $display);
    }

    /**
     * Test that sending empty options returns the expected results.
     */
    public function testSendWithEmptyOptions()
    {
        $server = factory(Server::class)->make();
        $server->setRelation('node', factory(Node::class)->make());

        $this->repository->expects('getServersForPowerActionCount')
            ->with([], [])
            ->andReturn(1);

        $this->repository->expects('getServersForPowerAction')->with([], [])->andReturn(Collection::make([$server]));
        $this->powerRepository->expects('setServer->send')->with('kill')->andReturnNull();

        $display = $this->runCommand($this->getCommand(), [
            'action' => 'kill',
            '--servers' => '',
            '--nodes' => '',
        ], ['yes']);

        $this->assertNotEmpty($display);
        $this->assertStringContainsString('1/1', $display);
        $this->assertStringContainsString(trans('command/messages.server.power.confirm', ['action' => 'kill', 'count' => 1]), $display);
    }

    /**
     * Test that validation occurs correctly.
     *
     * @param array $data
     *
     * @dataProvider validationFailureDataProvider
     */
    public function testValidationErrors(array $data)
    {
        $this->expectException(ValidationException::class);
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
