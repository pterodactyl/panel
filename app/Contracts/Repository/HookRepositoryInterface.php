<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Hook;
use Illuminate\Support\Collection;

interface HookRepositoryInterface extends RepositoryInterface
{
    /**
     * Return all the hooks for a given server.
     */
    public function findServerHooks(int $server): Collection;

    /**
     * Return a hook model with the associated trigger and action as a relationship.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getHookWithTriggerAndAction(int $schedule): Hook;
}
