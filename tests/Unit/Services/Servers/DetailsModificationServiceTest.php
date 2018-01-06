<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Servers;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\Servers\DetailsModificationService;
use Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService;
use Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService;
use Pterodactyl\Repositories\Daemon\ServerRepository as DaemonServerRepository;

class DetailsModificationServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Repositories\Daemon\ServerRepository|\Mockery\Mock
     */
    protected $daemonServerRepository;

    /**
     * @var \GuzzleHttp\Exception\RequestException|\Mockery\Mock
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService|\Mockery\Mock
     */
    protected $keyCreationService;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService|\Mockery\Mock
     */
    protected $keyDeletionService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Servers\DetailsModificationService
     */
    protected $service;

    /**
     * @var \Illuminate\Log\Writer|\Mockery\Mock
     */
    protected $writer;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->exception = m::mock(RequestException::class)->makePartial();
        $this->daemonServerRepository = m::mock(DaemonServerRepository::class);
        $this->keyCreationService = m::mock(DaemonKeyCreationService::class);
        $this->keyDeletionService = m::mock(DaemonKeyDeletionService::class);
        $this->repository = m::mock(ServerRepository::class);
        $this->writer = m::mock(Writer::class);

        $this->service = new DetailsModificationService(
            $this->connection,
            $this->keyCreationService,
            $this->keyDeletionService,
            $this->daemonServerRepository,
            $this->repository,
            $this->writer
        );
    }

    /**
     * Test basic updating of core variables when a model is provided.
     */
    public function testEditShouldSkipDatabaseSearchIfModelIsPassed()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
        ]);

        $data = ['owner_id' => 1, 'name' => 'New Name', 'description' => 'New Description'];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
            ], true, true)->once()->andReturnNull();

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->edit($server, $data);
        $this->assertTrue(true);
    }

    /**
     * Test that repository attempts to find model in database if no model is passed.
     */
    public function testEditShouldGetModelFromRepositoryIfNotPassed()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
        ]);

        $data = ['owner_id' => 1, 'name' => 'New Name', 'description' => 'New Description'];

        $this->repository->shouldReceive('find')->with($server->id)->once()->andReturn($server);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
            ], true, true)->once()->andReturnNull();

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->edit($server->id, $data);
        $this->assertTrue(true);
    }

    /**
     * Test that the daemon secret is reset if the owner id changes.
     */
    public function testEditShouldResetDaemonSecretIfOwnerIdIsChanged()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
            'node_id' => 1,
        ]);

        $data = ['owner_id' => 2, 'name' => 'New Name', 'description' => 'New Description'];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
            ], true, true)->once()->andReturnNull();

        $this->keyDeletionService->shouldReceive('handle')->with($server, $server->owner_id)->once()->andReturnNull();
        $this->keyCreationService->shouldReceive('handle')->with($server->id, $data['owner_id']);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->edit($server, $data);
        $this->assertTrue(true);
    }

    /**
     * Test that the docker image for a server can be updated if a model is provided.
     */
    public function testDockerImageCanBeUpdatedWhenAServerModelIsProvided()
    {
        $server = factory(Server::class)->make(['node_id' => 1]);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'image' => 'new/image',
            ])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('update')->with([
                'build' => [
                    'image' => 'new/image',
                ],
            ])->once()->andReturn(new Response);

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->setDockerImage($server, 'new/image');
        $this->assertTrue(true);
    }

    /**
     * Test that the docker image for a server can be updated if a model is provided.
     */
    public function testDockerImageCanBeUpdatedWhenNoModelIsProvided()
    {
        $server = factory(Server::class)->make(['node_id' => 1]);

        $this->repository->shouldReceive('find')->with($server->id)->once()->andReturn($server);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'image' => 'new/image',
            ])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('update')->with([
                'build' => [
                    'image' => 'new/image',
                ],
            ])->once()->andReturn(new Response);

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->setDockerImage($server->id, 'new/image');
        $this->assertTrue(true);
    }

    /**
     * Test that an exception thrown by Guzzle is rendered as a displayable exception.
     */
    public function testExceptionThrownByGuzzleWhenSettingDockerImageShouldBeRenderedAsADisplayableException()
    {
        $server = factory(Server::class)->make(['node_id' => 1]);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'image' => 'new/image',
            ])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setServer')->andThrow($this->exception);
        $this->connection->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('getStatusCode')->withNoArgs()->once()->andReturn(400);

        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();

        try {
            $this->service->setDockerImage($server, 'new/image');
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(
                trans('admin/server.exceptions.daemon_exception', ['code' => 400]),
                $exception->getMessage()
            );
        }
    }

    /**
     * Test that an exception not thrown by Guzzle is not transformed to a displayable exception.
     *
     * @expectedException \Exception
     */
    public function testExceptionNotThrownByGuzzleWhenSettingDockerImageShouldNotBeRenderedAsADisplayableException()
    {
        $server = factory(Server::class)->make(['node_id' => 1]);

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'image' => 'new/image',
            ])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->andThrow(new Exception());

        $this->service->setDockerImage($server, 'new/image');
    }
}
