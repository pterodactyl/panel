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
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Api\PermissionService;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var \Pterodactyl\Services\Api\PermissionService
     */
    protected $permissions;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Api\KeyCreationService
     */
    protected $service;

    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->permissions = m::mock(PermissionService::class);
        $this->repository = m::mock(ApiKeyRepositoryInterface::class);

        $this->service = new KeyCreationService(
            $this->repository,
            $this->connection,
            $this->encrypter,
            $this->permissions
        );
    }

    /**
     * Test that the service is able to create a keypair and assign the correct permissions.
     */
    public function testKeyIsCreated()
    {
        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'str_random')
            ->expects($this->exactly(2))->willReturn('random_string');

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with('random_string')->once()->andReturn('encrypted-secret');

        $this->repository->shouldReceive('create')->with([
            'test-data' => 'test',
            'public' => 'random_string',
            'secret' => 'encrypted-secret',
        ], true, true)->once()->andReturn((object) ['id' => 1]);

        $this->permissions->shouldReceive('getPermissions')->withNoArgs()->once()->andReturn([
            '_user' => ['server' => ['list']],
            'server' => ['create'],
        ]);

        $this->permissions->shouldReceive('create')->with(1, 'user.server-list')->once()->andReturnNull();
        $this->permissions->shouldReceive('create')->with(1, 'server-create')->once()->andReturnNull();

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle(
            ['test-data' => 'test'],
            ['invalid-node', 'server-list'],
            ['invalid-node', 'server-create']
        );

        $this->assertNotEmpty($response);
        $this->assertEquals('random_string', $response);
    }
}
