<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Services\Nests\NestUpdateService;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;

class NestUpdateServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Nests\NestUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(NestRepositoryInterface::class);

        $this->service = new NestUpdateService($this->repository);
    }

    /**
     * Test that the author key is removed from the data array before updating the record.
     */
    public function testAuthorArrayKeyIsRemovedIfPassed()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with(1, ['otherfield' => 'value'])->once()->andReturnNull();

        $this->service->handle(1, ['author' => 'author1', 'otherfield' => 'value']);
    }

    /**
     * Test that the function continues to work when no author key is passed.
     */
    public function testServiceIsUpdatedWhenNoAuthorKeyIsPassed()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with(1, ['otherfield' => 'value'])->once()->andReturnNull();

        $this->service->handle(1, ['otherfield' => 'value']);
    }
}
