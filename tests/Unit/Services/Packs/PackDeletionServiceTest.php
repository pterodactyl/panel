<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Packs;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Pack;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Packs\PackDeletionService;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class PackDeletionServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Packs\PackDeletionService
     */
    protected $service;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $storage;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->repository = m::mock(PackRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->storage = m::mock(Factory::class);

        $this->service = new PackDeletionService(
            $this->connection,
            $this->storage,
            $this->repository,
            $this->serverRepository
        );
    }

    /**
     * Test that a pack is deleted.
     */
    public function testPackIsDeleted()
    {
        $model = factory(Pack::class)->make();

        $this->serverRepository->shouldReceive('findCountWhere')->with([['pack_id', '=', $model->id]])->once()->andReturn(0);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($model->id)->once()->andReturn(1);
        $this->storage->shouldReceive('disk')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('deleteDirectory')->with('packs/' . $model->uuid)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($model);
    }

    /**
     * Test that a pack ID can be passed in place of the model.
     */
    public function testPackIdCanBePassedInPlaceOfModel()
    {
        $model = factory(Pack::class)->make();

        $this->repository->shouldReceive('setColumns')->with(['id', 'uuid'])->once()->andReturnSelf()
            ->shouldReceive('find')->with($model->id)->once()->andReturn($model);
        $this->serverRepository->shouldReceive('findCountWhere')->with([['pack_id', '=', $model->id]])->once()->andReturn(0);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($model->id)->once()->andReturn(1);
        $this->storage->shouldReceive('disk')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('deleteDirectory')->with('packs/' . $model->uuid)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($model->id);
    }

    /**
     * Test that an exception gets thrown if a server is attached to a pack.
     */
    public function testExceptionIsThrownIfServerIsAttachedToPack()
    {
        $model = factory(Pack::class)->make();

        $this->serverRepository->shouldReceive('findCountWhere')->with([['pack_id', '=', $model->id]])->once()->andReturn(1);

        try {
            $this->service->handle($model);
        } catch (HasActiveServersException $exception) {
            $this->assertEquals(trans('exceptions.packs.delete_has_servers'), $exception->getMessage());
        }
    }
}
