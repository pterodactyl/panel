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

namespace Tests\Unit\Services\Services;

use Mockery as m;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;
use Pterodactyl\Services\Services\ServiceUpdateService;
use Tests\TestCase;

class ServiceUpdateServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\ServiceUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(ServiceRepositoryInterface::class);

        $this->service = new ServiceUpdateService($this->repository);
    }

    /**
     * Test that the author key is removed from the data array before updating the record.
     */
    public function testAuthorArrayKeyIsRemovedIfPassed()
    {
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with(1, ['otherfield' => 'value'])->once()->andReturnNull();

        $this->service->handle(1, ['author' => 'author1', 'otherfield' => 'value']);
    }

    /**
     * Test that the function continues to work when no author key is passed.
     */
    public function testServiceIsUpdatedWhenNoAuthorKeyIsPassed()
    {
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with(1, ['otherfield' => 'value'])->once()->andReturnNull();

        $this->service->handle(1, ['otherfield' => 'value']);
    }
}
