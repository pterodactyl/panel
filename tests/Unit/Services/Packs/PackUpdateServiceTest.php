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
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
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
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($model->id, [
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
    public function testExceptionIsThrownIfModifyingOptionIdWhenServersAreAttached()
    {
        $model = factory(Pack::class)->make();
        $this->serverRepository->shouldReceive('findCountWhere')->with([['pack_id', '=', $model->id]])->once()->andReturn(1);

        try {
            $this->service->handle($model, ['option_id' => 0]);
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

        $this->repository->shouldReceive('withColumns')->with(['id', 'option_id'])->once()->andReturnSelf()
            ->shouldReceive('find')->with($model->id)->once()->andReturn($model);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($model->id, [
                'locked' => false,
                'visible' => false,
                'selectable' => false,
                'test-data' => 'value',
            ])->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle($model->id, ['test-data' => 'value']));
    }
}
