<?php

namespace Tests\Unit\Http\Middleware\Server;

use Mockery as m;
use Pterodactyl\Models\Server;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Http\Middleware\Server\AuthenticateAsSubuser;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class AuthenticateAsSubuserTest extends MiddlewareTestCase
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService|\Mockery\Mock
     */
    private $keyProviderService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->keyProviderService = m::mock(DaemonKeyProviderService::class);
    }

    /**
     * Test a successful instance of the middleware.
     */
    public function testSuccessfulMiddleware()
    {
        $model = factory(Server::class)->make();
        $user = $this->setRequestUser();
        $this->setRequestAttribute('server', $model);

        $this->keyProviderService->shouldReceive('handle')->with($model, $user)->once()->andReturn('abc123');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('server_token');
        $this->assertRequestAttributeEquals('abc123', 'server_token');
    }

    /**
     * Test middleware handles missing token exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @expectedExceptionMessage This account does not have permission to access this server.
     */
    public function testExceptionIsThrownIfNoTokenIsFound()
    {
        $model = factory(Server::class)->make();
        $user = $this->setRequestUser();
        $this->setRequestAttribute('server', $model);

        $this->keyProviderService->shouldReceive('handle')->with($model, $user)->once()->andThrow(new RecordNotFoundException);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\Server\AuthenticateAsSubuser
     */
    public function getMiddleware(): AuthenticateAsSubuser
    {
        return new AuthenticateAsSubuser($this->keyProviderService);
    }
}
