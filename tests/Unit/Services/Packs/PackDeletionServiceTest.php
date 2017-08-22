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

namespace Tests\Unit\Services\Packs;

use Exception;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\ConnectionInterface;
use Mockery as m;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Pack;
use Pterodactyl\Services\Packs\PackDeletionService;
use Tests\TestCase;

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
        $this->repository->shouldReceive('delete')->with($model->id)->once()->andReturnNull();
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

        $this->repository->shouldReceive('withColumns')->with(['id', 'uuid'])->once()->andReturnSelf()
            ->shouldReceive('find')->with($model->id)->once()->andReturn($model);
        $this->serverRepository->shouldReceive('findCountWhere')->with([['pack_id', '=', $model->id]])->once()->andReturn(0);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with($model->id)->once()->andReturnNull();
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
            $this->assertEquals(trans('admin/exceptions.packs.delete_has_servers'), $exception->getMessage());
        }
    }
}
