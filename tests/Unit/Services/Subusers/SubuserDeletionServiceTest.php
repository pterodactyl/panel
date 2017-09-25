<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
