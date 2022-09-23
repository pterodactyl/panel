<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Task;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class TaskRepository extends EloquentRepository implements TaskRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Task::class;
    }

    /**
     * Get a task and the server relationship for that task.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getTaskForJobProcess(int $id): Task
    {
        try {
            return $this->getBuilder()->with('server.user', 'schedule')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }

    /**
     * Returns the next task in a schedule.
     *
     * @return \Pterodactyl\Models\Task|null
     */
    public function getNextTask(int $schedule, int $index)
    {
        return $this->getBuilder()->where('schedule_id', '=', $schedule)
            ->orderBy('sequence_id', 'asc')
            ->where('sequence_id', '>', $index)
            ->first($this->getColumns());
    }
}
