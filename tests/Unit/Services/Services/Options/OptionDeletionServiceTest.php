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

namespace Tests\Unit\Services\Services\Options;

use Mockery as m;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Services\ServiceOption\HasActiveServersException;
use Pterodactyl\Services\Services\Options\OptionDeletionService;
use Tests\TestCase;

class OptionDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Services\Options\OptionDeletionService
     */
    protected $service;

    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(ServiceOptionRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);

        $this->service = new OptionDeletionService($this->serverRepository, $this->repository);
    }

    /**
     * Test that option is deleted if no servers are found.
     */
    public function testOptionIsDeletedIfNoServersAreFound()
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['option_id', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle(1));
    }

    /**
     * Test that option is not deleted if servers are found.
     */
    public function testExceptionIsThrownIfServersAreFound()
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['option_id', '=', 1]])->once()->andReturn(1);

        try {
            $this->service->handle(1);
        } catch (\Exception $exception) {
            $this->assertInstanceOf(HasActiveServersException::class, $exception);
            $this->assertEquals(trans('admin/exceptions.service.options.delete_has_servers'), $exception->getMessage());
        }
    }
}
