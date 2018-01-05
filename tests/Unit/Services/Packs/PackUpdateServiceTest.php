<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Packs;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Pack;
use Pterodactyl\Services\Packs\PackUpdateService;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class PackUpdateServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Packs\PackUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(PackRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);

        $this->service = new PackUpdateService($this->repository, $this->serverRepository);
    }

    /**
     * Test that a pack is updated.
     */
    public function testPackIsUpdated()
    {
        $model = factory(Pack::class)->make();
        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, [
                'locked' => false,
                'visible' => false,
                'selectable' => false,
                'test-data' => 'value',
            ])->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle($model, ['test-data' => 'value']));
    }

    /**
     * Test that an exception is thrown if the pack option ID is changed while servers are using the pack.
     */
    public function testExceptionIsThrownIfModifyingEggIdWhenServersAreAttached()
    {
        $model = factory(Pack::class)->make();
        $this->serverRepository->shouldReceive('findCountWhere')->with([['pack_id', '=', $model->id]])->once()->andReturn(1);

        try {
            $this->service->handle($model, ['egg_id' => 0]);
        } catch (HasActiveServersException $exception) {
            $this->assertEquals(trans('exceptions.packs.update_has_servers'), $exception->getMessage());
        }
    }

    /**
     * Test that an ID for a pack can be passed in place of the model.
     */
    public function testPackIdCanBePassedInPlaceOfModel()
    {
        $model = factory(Pack::class)->make();

        $this->repository->shouldReceive('setColumns')->with(['id', 'egg_id'])->once()->andReturnSelf()
            ->shouldReceive('find')->with($model->id)->once()->andReturn($model);
        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, [
                'locked' => false,
                'visible' => false,
                'selectable' => false,
                'test-data' => 'value',
            ])->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle($model->id, ['test-data' => 'value']));
    }
}
