<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services\Options;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Services\Services\Options\OptionDeletionService;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;

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
            $this->assertEquals(trans('exceptions.service.options.delete_has_servers'), $exception->getMessage());
        }
    }
}
