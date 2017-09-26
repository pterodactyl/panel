<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Subusers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Subuser;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Subusers\SubuserDeletionService;
use Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class SubuserDeletionServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyDeletionService|\Mockery\Mock
     */
    protected $keyDeletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserDeletionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->keyDeletionService = m::mock(DaemonKeyDeletionService::class);
        $this->repository = m::mock(SubuserRepositoryInterface::class);

        $this->service = new SubuserDeletionService(
            $this->connection,
            $this->keyDeletionService,
            $this->repository
        );
    }

    /**
     * Test that a subuser is deleted correctly.
     */
    public function testSubuserIsDeletedIfModelIsPassed()
    {
        $subuser = factory(Subuser::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->keyDeletionService->shouldReceive('handle')->with($subuser->server_id, $subuser->user_id)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($subuser->id)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($subuser);
        $this->assertTrue(true);
    }

    /**
     * Test that a subuser is deleted correctly if only the subuser ID is passed.
     */
    public function testSubuserIsDeletedIfIdPassedInPlaceOfModel()
    {
        $subuser = factory(Subuser::class)->make();

        $this->repository->shouldReceive('find')->with($subuser->id)->once()->andReturn($subuser);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->keyDeletionService->shouldReceive('handle')->with($subuser->server_id, $subuser->user_id)->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($subuser->id)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($subuser->id);
        $this->assertTrue(true);
    }
}
