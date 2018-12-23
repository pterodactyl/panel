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
use Pterodactyl\Services\Subusers\PermissionCreationService;
use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;

class PermissionCreationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Subusers\PermissionCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(PermissionRepositoryInterface::class);
        $this->service = new PermissionCreationService($this->repository);
    }

    /**
     * Test that permissions can be assigned correctly.
     */
    public function testPermissionsAreAssignedCorrectly()
    {
        $permissions = ['access-sftp'];

        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('insert')->with([
                ['subuser_id' => 1, 'permission' => 'access-sftp'],
            ])->once()->andReturn(true);

        $this->service->handle(1, $permissions);
        $this->assertTrue(true);
    }
}
