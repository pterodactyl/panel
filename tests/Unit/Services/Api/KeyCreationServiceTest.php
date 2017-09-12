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
        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'bin2hex')
            ->expects($this->exactly(2))->willReturn('bin2hex');

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with('bin2hex')->once()->andReturn('encrypted-secret');

        $this->repository->shouldReceive('create')->with([
            'test-data' => 'test',
            'public' => 'bin2hex',
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
        $this->assertEquals('bin2hex', $response);
    }
}
