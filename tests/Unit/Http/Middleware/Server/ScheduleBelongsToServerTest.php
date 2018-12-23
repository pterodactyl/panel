<?php

namespace Tests\Unit\Http\Middleware\Server;

use Mockery as m;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Http\Middleware\Server\ScheduleBelongsToServer;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleBelongsToServerTest extends MiddlewareTestCase
{
    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface|\Mockery\Mock
     */
    private $hashids;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    public function setUp()
    {
        parent::setUp();

        $this->hashids = m::mock(HashidsInterface::class);
        $this->repository = m::mock(ScheduleRepositoryInterface::class);
    }

    /**
     * Test a successful middleware instance.
     */
    public function testSuccessfulMiddleware()
    {
        $model = factory(Server::class)->make();
        $schedule = factory(Schedule::class)->make([
            'server_id' => $model->id,
        ]);
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('route->parameter')->with('schedule')->once()->andReturn('abc123');
        $this->hashids->shouldReceive('decodeFirst')->with('abc123', 0)->once()->andReturn($schedule->id);
        $this->repository->shouldReceive('getScheduleWithTasks')->with($schedule->id)->once()->andReturn($schedule);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('schedule');
        $this->assertRequestAttributeEquals($schedule, 'schedule');
    }

    /**
     * Test that an exception is thrown if the schedule does not belong to
     * the request server.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testExceptionIsThrownIfScheduleDoesNotBelongToServer()
    {
        $model = factory(Server::class)->make();
        $schedule = factory(Schedule::class)->make();
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('route->parameter')->with('schedule')->once()->andReturn('abc123');
        $this->hashids->shouldReceive('decodeFirst')->with('abc123', 0)->once()->andReturn($schedule->id);
        $this->repository->shouldReceive('getScheduleWithTasks')->with($schedule->id)->once()->andReturn($schedule);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\Server\ScheduleBelongsToServer
     */
    private function getMiddleware(): ScheduleBelongsToServer
    {
        return new ScheduleBelongsToServer($this->hashids, $this->repository);
    }
}
