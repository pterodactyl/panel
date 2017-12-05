<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Api;

use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\APIKey;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Api\PermissionService;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Pterodactyl\Services\Api\PermissionService|\Mockery\Mock
     */
    private $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->permissionService = m::mock(PermissionService::class);
        $this->repository = m::mock(ApiKeyRepositoryInterface::class);
    }

    /**
     * Test that the service is able to create a keypair and assign the correct permissions.
     */
    public function testKeyIsCreated()
    {
        $model = factory(APIKey::class)->make();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'str_random')
            ->expects($this->exactly(1))->with(APIKey::KEY_LENGTH)->willReturn($model->token);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->repository->shouldReceive('create')->with([
            'test-data' => 'test',
            'token' => $model->token,
        ], true, true)->once()->andReturn($model);

        $this->permissionService->shouldReceive('getPermissions')->withNoArgs()->once()->andReturn([
            '_user' => ['server' => ['list', 'multiple-dash-test']],
            'server' => ['create', 'admin-dash-test'],
        ]);

        $this->permissionService->shouldReceive('create')->with($model->id, 'user.server-list')->once()->andReturnNull();
        $this->permissionService->shouldReceive('create')->with($model->id, 'user.server-multiple-dash-test')->once()->andReturnNull();
        $this->permissionService->shouldReceive('create')->with($model->id, 'server-create')->once()->andReturnNull();
        $this->permissionService->shouldReceive('create')->with($model->id, 'server-admin-dash-test')->once()->andReturnNull();

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->getService()->handle(
            ['test-data' => 'test'],
            ['invalid-node', 'server-list', 'server-multiple-dash-test'],
            ['invalid-node', 'server-create', 'server-admin-dash-test']
        );

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(APIKey::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Return an instance of the service with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Services\Api\KeyCreationService
     */
    private function getService(): KeyCreationService
    {
        return new KeyCreationService($this->repository, $this->connection, $this->permissionService);
    }
}
