<?php

namespace Tests\Unit\Services\Helpers;

use Mockery as m;
use Tests\TestCase;
use Tests\Traits\MocksUuids;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;

class TemporaryPasswordServiceTest extends TestCase
{
    use MocksUuids;

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

        $this->connection = m::mock(ConnectionInterface::class);
        $this->hasher = m::mock(Hasher::class);

        $this->service = new TemporaryPasswordService($this->connection, $this->hasher);
    }

    /**
     * Test that a temporary password is stored and the token is returned.
     */
    public function testTemporaryPasswordIsStored()
    {
        $token = hash_hmac(TemporaryPasswordService::HMAC_ALGO, $this->getKnownUuid(), config('app.key'));

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
