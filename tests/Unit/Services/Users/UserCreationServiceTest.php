<?php

namespace Tests\Unit\Services;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Tests\Traits\MocksUuids;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Auth\PasswordBroker;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserCreationServiceTest extends TestCase
{
    use MocksUuids;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher|\Mockery\Mock
     */
    private $hasher;

    /**
     * @var \Illuminate\Contracts\Auth\PasswordBroker|\Mockery\Mock
     */
    private $passwordBroker;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        Notification::fake();
        $this->connection = m::mock(ConnectionInterface::class);
        $this->hasher = m::mock(Hasher::class);
        $this->passwordBroker = m::mock(PasswordBroker::class);
        $this->repository = m::mock(UserRepositoryInterface::class);
    }

    /**
     * Test that a user is created when a password is passed.
     */
    public function testUserIsCreatedWhenPasswordIsProvided()
    {
        $user = factory(User::class)->make();

        $this->hasher->shouldReceive('make')->with('raw-password')->once()->andReturn('enc-password');
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'password' => 'enc-password',
            'uuid' => $this->getKnownUuid(),
        ], true, true)->once()->andReturn($user);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->getService()->handle([
            'password' => 'raw-password',
        ]);

        $this->assertNotNull($response);
        Notification::assertSentTo($user, AccountCreated::class, function ($notification) use ($user) {
            $this->assertSame($user, $notification->user);
            $this->assertNull($notification->token);

            return true;
        });
    }

    /**
     * Test that a UUID passed in the submission data is not used when
     * creating the user.
     */
    public function testUuidPassedInDataIsIgnored()
    {
        $user = factory(User::class)->make();

        $this->hasher->shouldReceive('make')->andReturn('enc-password');
        $this->connection->shouldReceive('beginTransaction')->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'password' => 'enc-password',
            'uuid' => $this->getKnownUuid(),
        ], true, true)->once()->andReturn($user);
        $this->connection->shouldReceive('commit')->andReturnNull();

        $response = $this->getService()->handle([
            'password' => 'raw-password',
            'uuid' => 'test-uuid',
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(User::class, $response);
        Notification::assertSentTo($user, AccountCreated::class, function ($notification) use ($user) {
            $this->assertSame($user, $notification->user);
            $this->assertNull($notification->token);

            return true;
        });
    }

    /**
     * Test that a user is created with a random password when no password is provided.
     */
    public function testUserIsCreatedWhenNoPasswordIsProvided()
    {
        $user = factory(User::class)->make();

        $this->hasher->shouldNotReceive('make');
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->hasher->shouldReceive('make')->once()->andReturn('created-enc-password');
        $this->passwordBroker->shouldReceive('createToken')->with($user)->once()->andReturn('random-token');

        $this->repository->shouldReceive('create')->with([
            'password' => 'created-enc-password',
            'email' => $user->email,
            'uuid' => $this->getKnownUuid(),
        ], true, true)->once()->andReturn($user);

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->getService()->handle([
            'email' => $user->email,
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(User::class, $response);
        Notification::assertSentTo($user, AccountCreated::class, function ($notification) use ($user) {
            $this->assertSame($user, $notification->user);
            $this->assertSame('random-token', $notification->token);

            return true;
        });
    }

    /**
     * Return a new instance of the service using mocked dependencies.
     *
     * @return \Pterodactyl\Services\Users\UserCreationService
     */
    private function getService(): UserCreationService
    {
        return new UserCreationService($this->connection, $this->hasher, $this->passwordBroker, $this->repository);
    }
}
