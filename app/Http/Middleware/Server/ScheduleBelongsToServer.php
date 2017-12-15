<?php

namespace Pterodactyl\Http\Middleware\Server;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduleBelongsToServer
{
    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface
     */
    private $hashids;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    private $repository;

    /**
     * TaskAccess constructor.
     *
     * @param \Pterodactyl\Contracts\Extensions\HashidsInterface            $hashids
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $repository
     */
    public function __construct(HashidsInterface $hashids, ScheduleRepositoryInterface $repository)
    {
        $this->hashids = $hashids;
        $this->repository = $repository;
    }

    /**
     * Determine if a task is assigned to the active server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->attributes->get('server');

        $scheduleId = $this->hashids->decodeFirst($request->route()->parameter('schedule'), 0);
        $schedule = $this->repository->getScheduleWithTasks($scheduleId);

        if ($schedule->server_id !== $server->id) {
            throw new NotFoundHttpException;
        }

        $request->attributes->set('schedule', $schedule);

        return $next($request);
    }
}
