<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware\Api;

use Mockery as m;
use Carbon\CarbonImmutable;
use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Http\Middleware\Api\AuthenticateKey;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateKeyTest extends MiddlewareTestCase
{
    /**
     * @var \Illuminate\Auth\AuthManager|\Mockery\Mock
     */
    private $auth;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter|\Mockery\Mock
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->auth = m::mock(AuthManager::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->repository = m::mock(ApiKeyRepositoryInterface::class);
    }

    /**
     * Test that a missing bearer token will throw an exception.
     */
    public function testMissingBearerTokenThrowsException()
    {
        $this->request->shouldReceive('user')->andReturnNull();
        $this->request->shouldReceive('bearerToken')->withNoArgs()->once()->andReturnNull();

        try {
            $this->getMiddleware()->handle($this->request, $this->getClosureAssertions(), ApiKey::TYPE_APPLICATION);
        } catch (HttpException $exception) {
            $this->assertEquals(401, $exception->getStatusCode());
            $this->assertEquals(['WWW-Authenticate' => 'Bearer'], $exception->getHeaders());
        }
    }

    /**
     * Test that an invalid API identifier throws an exception.
     */
    public function testInvalidIdentifier()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->request->shouldReceive('bearerToken')->withNoArgs()->twice()->andReturn('abcd1234');
        $this->repository->shouldReceive('findFirstWhere')->andThrow(new RecordNotFoundException());

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions(), ApiKey::TYPE_APPLICATION);
    }

    /**
     * Test that a valid token can continue past the middleware.
     */
    public function testValidToken()
    {
        $model = ApiKey::factory()->make();

        $this->request->shouldReceive('bearerToken')->withNoArgs()->twice()->andReturn($model->identifier . 'decrypted');
        $this->repository->shouldReceive('findFirstWhere')->with([
            ['identifier', '=', $model->identifier],
            ['key_type', '=', ApiKey::TYPE_APPLICATION],
        ])->once()->andReturn($model);
        $this->encrypter->shouldReceive('decrypt')->with($model->token)->once()->andReturn('decrypted');
        $this->auth->shouldReceive('guard->loginUsingId')->with($model->user_id)->once()->andReturnNull();

        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, [
            'last_used_at' => CarbonImmutable::now(),
        ])->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions(), ApiKey::TYPE_APPLICATION);
        $this->assertEquals($model, $this->request->attributes->get('api_key'));
    }

    /**
     * Test that a valid token can continue past the middleware when set as a user token.
     */
    public function testValidTokenWithUserKey()
    {
        $model = ApiKey::factory()->make();

        $this->request->shouldReceive('bearerToken')->withNoArgs()->twice()->andReturn($model->identifier . 'decrypted');
        $this->repository->shouldReceive('findFirstWhere')->with([
            ['identifier', '=', $model->identifier],
            ['key_type', '=', ApiKey::TYPE_ACCOUNT],
        ])->once()->andReturn($model);
        $this->encrypter->shouldReceive('decrypt')->with($model->token)->once()->andReturn('decrypted');
        $this->auth->shouldReceive('guard->loginUsingId')->with($model->user_id)->once()->andReturnNull();

        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, [
            'last_used_at' => CarbonImmutable::now(),
        ])->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions(), ApiKey::TYPE_ACCOUNT);
        $this->assertEquals($model, $this->request->attributes->get('api_key'));
    }

    /**
     * Test that we can still make it though this middleware if the user is logged in and passing
     * through a cookie.
     */
    public function testAccessWithoutToken()
    {
        $user = User::factory()->make(['id' => 123]);

        $this->request->shouldReceive('user')->andReturn($user);
        $this->request->shouldReceive('bearerToken')->withNoArgs()->twice()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions(), ApiKey::TYPE_ACCOUNT);
        $model = $this->request->attributes->get('api_key');

        $this->assertSame(ApiKey::TYPE_ACCOUNT, $model->key_type);
        $this->assertSame(123, $model->user_id);
        $this->assertNull($model->identifier);
    }

    /**
     * Test that a valid token identifier with an invalid token attached to it
     * triggers an exception.
     */
    public function testInvalidTokenForIdentifier()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $model = ApiKey::factory()->make();

        $this->request->shouldReceive('bearerToken')->withNoArgs()->twice()->andReturn($model->identifier . 'asdf');
        $this->repository->shouldReceive('findFirstWhere')->with([
            ['identifier', '=', $model->identifier],
            ['key_type', '=', ApiKey::TYPE_APPLICATION],
        ])->once()->andReturn($model);
        $this->encrypter->shouldReceive('decrypt')->with($model->token)->once()->andReturn('decrypted');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions(), ApiKey::TYPE_APPLICATION);
    }

    /**
     * Return an instance of the middleware with mocked dependencies for testing.
     */
    private function getMiddleware(): AuthenticateKey
    {
        return new AuthenticateKey($this->repository, $this->auth, $this->encrypter);
    }
}
