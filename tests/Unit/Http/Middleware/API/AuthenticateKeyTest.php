<?php

namespace Tests\Unit\Http\Middleware\API;

use Mockery as m;
use Pterodactyl\Models\APIKey;
use Illuminate\Auth\AuthManager;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Http\Middleware\API\AuthenticateKey;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class AuthenticateKeyTest extends MiddlewareTestCase
{
    /**
     * @var \Illuminate\Auth\AuthManager|\Mockery\Mock
     */
    private $auth;

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

        $this->auth = m::mock(AuthManager::class);
        $this->repository = m::mock(ApiKeyRepositoryInterface::class);
    }

    /**
     * Test that a missing bearer token will throw an exception.
     */
    public function testMissingBearerTokenThrowsException()
    {
        $this->request->shouldReceive('bearerToken')->withNoArgs()->once()->andReturnNull();

        try {
            $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        } catch (HttpException $exception) {
            $this->assertEquals(401, $exception->getStatusCode());
            $this->assertEquals(['WWW-Authenticate' => 'Bearer'], $exception->getHeaders());
        }
    }

    /**
     * Test that an invalid API token throws an exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testInvalidTokenThrowsException()
    {
        $this->request->shouldReceive('bearerToken')->withNoArgs()->twice()->andReturn('abcd1234');
        $this->repository->shouldReceive('findFirstWhere')->andThrow(new RecordNotFoundException);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that a valid token can continue past the middleware.
     */
    public function testValidToken()
    {
        $model = factory(APIKey::class)->make();

        $this->request->shouldReceive('bearerToken')->withNoArgs()->twice()->andReturn($model->token);
        $this->repository->shouldReceive('findFirstWhere')->with([['token', '=', $model->token]])->once()->andReturn($model);

        $this->auth->shouldReceive('guard->loginUsingId')->with($model->user_id)->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertEquals($model, $this->request->attributes->get('api_key'));
    }

    /**
     * Return an instance of the middleware with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Http\Middleware\API\AuthenticateKey
     */
    private function getMiddleware(): AuthenticateKey
    {
        return new AuthenticateKey($this->repository, $this->auth);
    }
}
