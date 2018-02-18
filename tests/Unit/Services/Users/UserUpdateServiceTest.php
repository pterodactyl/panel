<?php

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Services\DaemonKeys\RevokeMultipleDaemonKeysService;

class UserUpdateServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Hashing\Hasher|\Mockery\Mock
     */
    private $hasher;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\RevokeMultipleDaemonKeysService|\Mockery\Mock
     */
    private $revocationService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->hasher = m::mock(Hasher::class);
        $this->repository = m::mock(UserRepositoryInterface::class);
        $this->revocationService = m::mock(RevokeMultipleDaemonKeysService::class);
    }

    /**
     * Test that the handle function does not attempt to hash a password if no
     * password is provided or the password is null.
     *
     * @dataProvider badPasswordDataProvider
     */
    public function testUpdateUserWithoutTouchingHasherIfNoPasswordPassed(array $data)
    {
        $user = factory(User::class)->make();
        $this->revocationService->shouldReceive('getExceptions')->withNoArgs()->once()->andReturn([]);
        $this->repository->shouldReceive('update')->with($user->id, ['test-data' => 'value'])->once()->andReturnNull();

        $response = $this->getService()->handle($user, $data);
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertTrue($response->has('model'));
        $this->assertTrue($response->has('exceptions'));
    }

    /**
     * Provide a test data set with passwords that should not be hashed.
     *
     * @return array
     */
    public function badPasswordDataProvider(): array
    {
        return [
            [['test-data' => 'value']],
            [['test-data' => 'value', 'password' => null]],
            [['test-data' => 'value', 'password' => '']],
            [['test-data' => 'value', 'password' => 0]],
        ];
    }

    /**
     * Test that the handle function hashes a password if passed in the data array.
     */
    public function testUpdateUserAndHashPasswordIfProvided()
    {
        $user = factory(User::class)->make();
        $this->hasher->shouldReceive('make')->with('raw_pass')->once()->andReturn('enc_pass');
        $this->revocationService->shouldReceive('getExceptions')->withNoArgs()->once()->andReturn([]);
        $this->repository->shouldReceive('update')->with($user->id, ['password' => 'enc_pass'])->once()->andReturnNull();

        $response = $this->getService()->handle($user, ['password' => 'raw_pass']);
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertTrue($response->has('model'));
        $this->assertTrue($response->has('exceptions'));
    }

    /**
     * Test that an admin can revoke a user's administrative status.
     */
    public function testAdministrativeUserRevokingAdminStatus()
    {
        $user = factory(User::class)->make(['root_admin' => true]);
        $service = $this->getService();
        $service->setUserLevel(User::USER_LEVEL_ADMIN);

        $this->revocationService->shouldReceive('handle')->with($user, false)->once()->andReturnNull();
        $this->revocationService->shouldReceive('getExceptions')->withNoArgs()->once()->andReturn([]);
        $this->repository->shouldReceive('update')->with($user->id, ['root_admin' => false])->once()->andReturnNull();

        $response = $service->handle($user, ['root_admin' => false]);
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertTrue($response->has('model'));
        $this->assertTrue($response->has('exceptions'));
    }

    /**
     * Test that a normal user is unable to set an administrative status for themselves.
     */
    public function testNormalUserShouldNotRevokeAdminStatus()
    {
        $user = factory(User::class)->make(['root_admin' => false]);
        $service = $this->getService();
        $service->setUserLevel(User::USER_LEVEL_USER);

        $this->revocationService->shouldReceive('getExceptions')->withNoArgs()->once()->andReturn([]);
        $this->repository->shouldReceive('update')->with($user->id, [])->once()->andReturnNull();

        $response = $service->handle($user, ['root_admin' => true]);
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertTrue($response->has('model'));
        $this->assertTrue($response->has('exceptions'));
    }

    /**
     * Return an instance of the service for testing.
     *
     * @return \Pterodactyl\Services\Users\UserUpdateService
     */
    private function getService(): UserUpdateService
    {
        return new UserUpdateService($this->hasher, $this->revocationService, $this->repository);
    }
}
