<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class HookRepository extends EloquentRepository
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return Hook::class;
    }

    /**
     * Return all hooks for a given server.
     */
    public function findServerHooks(int $server): Collection
    {
        return $this->getBuilder()
            ->with(['trigger', 'action'])
            ->where('server_id', '=', $server)
            ->get($this->getColumns());
    }

    /**
     * Return a hook model with the associated  trigger and action relationships.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getHookWithTriggerAndAction(int $hook): Hook
    {
        try {
            return $this->getBuilder()
                ->with(['trigger', 'action'])
                ->findOrFail($hook, $this->getColumns());
        } catch (ModelNotFoundException) {
            throw new RecordNotFoundException();
        }
    }
}
