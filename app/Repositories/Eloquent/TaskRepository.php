<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Contracts\Repository\TaskRepositoryInterface;
use App\Exceptions\Repository\RecordNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
     * @param int $id
     * @return \App\Models\Task
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function getTaskForJobProcess(int $id): Task
    {
        try {
            return $this->getBuilder()->with('server.user', 'schedule')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException;
        }
    }

    /**
     * Returns the next task in a schedule.
     *
     * @param int $schedule
     * @param int $index
     * @return null|\App\Models\Task
     */
    public function getNextTask(int $schedule, int $index)
    {
        return $this->getBuilder()->where('schedule_id', '=', $schedule)
            ->where('sequence_id', '=', $index + 1)
            ->first($this->getColumns());
    }
}
