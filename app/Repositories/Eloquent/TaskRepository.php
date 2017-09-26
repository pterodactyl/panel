<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Task;
use Webmozart\Assert\Assert;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class TaskRepository extends EloquentRepository implements TaskRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Task::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getTaskWithServer($id)
    {
        Assert::integerish($id, 'First argument passed to getTaskWithServer must be numeric, received %s.');

        $instance = $this->getBuilder()->with('server')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getNextTask($schedule, $index)
    {
        Assert::integerish($schedule, 'First argument passed to getNextTask must be integer, received %s.');
        Assert::integerish($index, 'Second argument passed to getNextTask must be integer, received %s.');

        return $this->getBuilder()->where('schedule_id', '=', $schedule)
            ->where('sequence_id', '=', $index + 1)
            ->first($this->getColumns());
    }
}
