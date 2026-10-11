<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Actions\Schedules\CreateScheduleTask;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Task;

class StoreTaskRequest extends ViewScheduleRequest
{
    /**
     * Creating or changing a task needs permission to update the schedule.
     */
    public function permission(): string
    {
        return Permissions::ScheduleUpdate->value;
    }

    /**
     * The user also needs the permission for the task's action, such as console access for
     * a command task. An unknown action or power signal is left to validation to reject.
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $permission = Task::permissionForAction($this->string('action')->toString(), $this->string('payload')->toString());

        return ! $permission instanceof Permissions || $this->user()->can($permission->value, $this->parameter('server', Server::class));
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateScheduleTask::rules($this->input('action') === Task::ACTION_POWER);
    }
}
