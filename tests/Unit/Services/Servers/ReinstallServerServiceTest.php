<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class ReinstallServerServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = m::mock(ServerRepository::class);
        $this->connection = m::mock(ConnectionInterface::class);
        $this->daemonServerRepository = m::mock(DaemonServerRepository::class);
    }

    /**
     * Test that a server is reinstalled when it's model is passed to the function.
     */
    public function testServerShouldBeReinstalledWhenModelIsPassed()
    {
        /** @var \Pterodactyl\Models\Server $server */
        $server = factory(Server::class)->make(['id' => 123]);
        $updated = clone $server;
        $updated->installed = Server::STATUS_INSTALLING;

        $this->connection->expects('transaction')->with(m::on(function ($closure) use ($updated) {
            return $closure() instanceof Server;
        }))->andReturn($updated);

        $this->repository->expects('update')->with($server->id, [
            'installed' => Server::STATUS_INSTALLING,
        ])->andReturns($updated);

        $this->daemonServerRepository->expects('setServer')->with($server)->andReturnSelf();
        $this->daemonServerRepository->expects('reinstall')->withNoArgs();

        $this->assertSame($updated, $this->getService()->reinstall($server));
    }

    /**
     * @return \Pterodactyl\Services\Servers\ReinstallServerService
     */
    private function getService()
    {
        return new ReinstallServerService(
            $this->connection, $this->daemonServerRepository, $this->repository
        );
    }
}
