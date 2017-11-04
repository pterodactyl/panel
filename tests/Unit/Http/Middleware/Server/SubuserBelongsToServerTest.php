<?php

namespace Tests\Unit\Http\Middleware\Server;

use Mockery as m;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\PterodactylException;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Http\Middleware\Server\SubuserBelongsToServer;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class SubuserBelongsToServerTest extends MiddlewareTestCase
{
    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface|\Mockery\Mock
     */
    private $hashids;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->hashids = m::mock(HashidsInterface::class);
        $this->repository = m::mock(SubuserRepositoryInterface::class);
    }

    /**
     * Test a successful middleware instance.
     */
    public function testSuccessfulMiddleware()
    {
        $model = factory(Server::class)->make();
        $subuser = factory(Subuser::class)->make([
            'server_id' => $model->id,
        ]);
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('route->parameter')->with('subuser', 0)->once()->andReturn('abc123');
        $this->hashids->shouldReceive('decodeFirst')->with('abc123', 0)->once()->andReturn($subuser->id);
        $this->repository->shouldReceive('find')->with($subuser->id)->once()->andReturn($subuser);

        $this->request->shouldReceive('method')->withNoArgs()->once()->andReturn('GET');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('subuser');
        $this->assertRequestAttributeEquals($subuser, 'subuser');
    }

    /**
     * Test that a user can edit a user other than themselves.
     */
    public function testSuccessfulMiddlewareWhenPatchRequest()
    {
        $this->setRequestUser();
        $model = factory(Server::class)->make();
        $subuser = factory(Subuser::class)->make([
            'server_id' => $model->id,
        ]);
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('route->parameter')->with('subuser', 0)->once()->andReturn('abc123');
        $this->hashids->shouldReceive('decodeFirst')->with('abc123', 0)->once()->andReturn($subuser->id);
        $this->repository->shouldReceive('find')->with($subuser->id)->once()->andReturn($subuser);

        $this->request->shouldReceive('method')->withNoArgs()->once()->andReturn('PATCH');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('subuser');
        $this->assertRequestAttributeEquals($subuser, 'subuser');
    }

    /**
     * Test that an exception is thrown if a user attempts to edit themself.
     */
    public function testExceptionIsThrownIfUserTriesToEditSelf()
    {
        $user = $this->setRequestUser();
        $model = factory(Server::class)->make();
        $subuser = factory(Subuser::class)->make([
            'server_id' => $model->id,
            'user_id' => $user->id,
        ]);
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('route->parameter')->with('subuser', 0)->once()->andReturn('abc123');
        $this->hashids->shouldReceive('decodeFirst')->with('abc123', 0)->once()->andReturn($subuser->id);
        $this->repository->shouldReceive('find')->with($subuser->id)->once()->andReturn($subuser);

        $this->request->shouldReceive('method')->withNoArgs()->once()->andReturn('PATCH');

        try {
            $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(trans('exceptions.subusers.editing_self'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if a subuser server does not match the
     * request server.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testExceptionIsThrownIfSubuserServerDoesNotMatchRequestServer()
    {
        $model = factory(Server::class)->make();
        $subuser = factory(Subuser::class)->make();
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('route->parameter')->with('subuser', 0)->once()->andReturn('abc123');
        $this->hashids->shouldReceive('decodeFirst')->with('abc123', 0)->once()->andReturn($subuser->id);
        $this->repository->shouldReceive('find')->with($subuser->id)->once()->andReturn($subuser);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an exception is thrown if no subuser is found.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testExceptionIsThrownIfNoSubuserIsFound()
    {
        $model = factory(Server::class)->make();
        $subuser = factory(Subuser::class)->make();
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('route->parameter')->with('subuser', 0)->once()->andReturn('abc123');
        $this->hashids->shouldReceive('decodeFirst')->with('abc123', 0)->once()->andReturn($subuser->id);
        $this->repository->shouldReceive('find')->with($subuser->id)->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\Server\SubuserBelongsToServer
     */
    private function getMiddleware(): SubuserBelongsToServer
    {
        return new SubuserBelongsToServer($this->hashids, $this->repository);
    }
}
