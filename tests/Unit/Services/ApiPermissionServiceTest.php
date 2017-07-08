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

namespace Tests\Unit\Services;

use Mockery as m;
use Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface;
use Pterodactyl\Models\APIPermission;
use Pterodactyl\Services\ApiPermissionService;
use Tests\TestCase;

class ApiPermissionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\ApiPermissionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(ApiPermissionRepositoryInterface::class);
        $this->service = new ApiPermissionService($this->repository);
    }

    /**
     * Test that a new API permission can be assigned to a key.
     */
    public function test_create_function()
    {
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
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
