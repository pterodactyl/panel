<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Helpers;

use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;

class TemporaryPasswordServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher|\Mockery\Mock
     */
    protected $hasher;

    /**
     * @var \Pterodactyl\Services\Helpers\TemporaryPasswordService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->connection = m::mock(ConnectionInterface::class);
        $this->hasher = m::mock(Hasher::class);

        $this->service = new TemporaryPasswordService($this->config, $this->connection, $this->hasher);
    }

    /**
     * Test that a temporary password is stored and the token is returned.
     */
    public function testTemporaryPasswordIsStored()
    {
        $this->getFunctionMock('\\Pterodactyl\\Services\\Helpers', 'str_random')
            ->expects($this->once())->with(40)->willReturn('random_string');

        $this->config->shouldReceive('get')->with('app.key')->once()->andReturn('123456');
        $token = hash_hmac(TemporaryPasswordService::HMAC_ALGO, 'random_string', '123456');

        $this->hasher->shouldReceive('make')->with($token)->once()->andReturn('hashed_token');
        $this->connection->shouldReceive('table')->with('password_resets')->once()->andReturnSelf();
        $this->connection->shouldReceive('insert')->with([
            'email' => 'test@example.com',
            'token' => 'hashed_token',
        ])->once()->andReturnNull();

        $response = $this->service->handle('test@example.com');
        $this->assertNotEmpty($response);
        $this->assertEquals($token, $response);
    }
}
