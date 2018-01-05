<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\APIPermission;
use Pterodactyl\Services\Api\PermissionService;
use Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface;

class PermissionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Api\PermissionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(ApiPermissionRepositoryInterface::class);
        $this->service = new PermissionService($this->repository);
    }

    /**
     * Test that a new API permission can be assigned to a key.
     */
    public function test_create_function()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('create')->with(['key_id' => 1, 'permission' => 'test-permission'])
            ->once()->andReturn(true);

        $this->assertTrue($this->service->create(1, 'test-permission'));
    }

    /**
     * Test that function returns an array of all the permissions available as defined on the model.
     */
    public function test_get_permissions_function()
    {
        $this->repository->shouldReceive('getModel')->withNoArgs()->once()->andReturn(new APIPermission());

        $this->assertEquals(APIPermission::CONST_PERMISSIONS, $this->service->getPermissions());
    }
}
