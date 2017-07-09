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

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\ConnectionInterface;
use Mockery as m;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;
use Pterodactyl\Services\Api\KeyService;
use Pterodactyl\Services\Api\PermissionService;
use Tests\TestCase;

class KeyServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

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
     * @var \Pterodactyl\Services\Api\KeyService
     */
    protected $service;

    public function setUp()
    {
        parent::setUp();

        $this->database = m::mock(ConnectionInterface::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->permissions = m::mock(PermissionService::class);
        $this->repository = m::mock(ApiKeyRepositoryInterface::class);

        $this->service = new KeyService(
            $this->repository, $this->database, $this->encrypter, $this->permissions
        );
    }

    /**
     * Test that the service is able to create a keypair and assign the correct permissions.
     */
    public function test_create_function()
    {
        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'random_bytes')
            ->expects($this->exactly(2))
            ->willReturnCallback(function ($bytes) {
                return hex2bin(str_pad('', $bytes * 2, '0'));
            });

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with(str_pad('', 64, '0'))
            ->once()->andReturn('encrypted-secret');

        $this->repository->shouldReceive('create')->with([
            'test-data' => 'test',
            'public' => str_pad('', 16, '0'),
            'secret' => 'encrypted-secret',
        ], true, true)->once()->andReturn((object) ['id' => 1]);

        $this->permissions->shouldReceive('getPermissions')->withNoArgs()->once()->andReturn([
            '_user' => ['server' => ['list']],
            'server' => ['create'],
        ]);

        $this->permissions->shouldReceive('create')->with(1, 'user.server-list')->once()->andReturnNull();
        $this->permissions->shouldReceive('create')->with(1, 'server-create')->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->create(
            ['test-data' => 'test'], ['invalid-node', 'server-list'], ['invalid-node', 'server-create']
        );

        $this->assertNotEmpty($response);
        $this->assertEquals(str_pad('', 64, '0'), $response);
    }

    /**
     * Test that an API key can be revoked.
     */
    public function test_revoke_function()
    {
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(true);

        $this->assertTrue($this->service->revoke(1));
    }
}
