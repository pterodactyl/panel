<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Middleware\Server;

use Closure;
use Illuminate\Contracts\Session\Session;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleAccess
{
    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface
     */
    protected $hashids;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * TaskAccess constructor.
     *
     * @param \Pterodactyl\Contracts\Extensions\HashidsInterface            $hashids
     * @param \Illuminate\Contracts\Session\Session                         $session
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $repository
     */
    public function __construct(
        HashidsInterface $hashids,
        Session $session,
        ScheduleRepositoryInterface $repository
    ) {
        $this->hashids = $hashids;
        $this->repository = $repository;
        $this->session = $session;
    }

    /**
     * Determine if a task is assigned to the active server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($request, Closure $next)
    {
        $server = $this->session->get('server_data.model');

        $scheduleId = $this->hashids->decodeFirst($request->route()->parameter('schedule'), 0);
        $schedule = $this->repository->getScheduleWithTasks($scheduleId);

        if (object_get($schedule, 'server_id') !== $server->id) {
            abort(404);
        }

        $request->attributes->set('schedule', $schedule);

        return $next($request);
    }
}
