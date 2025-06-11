<?php

namespace Pterodactyl\Services\Hooks;


use Illuminate\Console\Scheduling\Schedule;
use Pterodactyl\Models\Hook;
use Pterodactyl\Services\Schedules\ProcessScheduleService;

class HookExecutionService
{
    /**
     * HookExecutionService constructor.
     */
    public function __construct(private ProcessScheduleService $scheduleService) {}

    /**
     * Execute a hook
     *
     */
    public function handle(Hook $hook): void
    {
        $action = $hook->action;

        if (!$action) {
            return;
        }

        $config = json_decode($action->config, true);

        switch ($action->type) {
            case 'run_schedule':
                $scheduleId = $config[0] ?? null;
                if (!$scheduleId) break;

                $schedule = Schedule::where('id', $scheduleId)
                    ->where('server_id', $hook->server_id)
                    ->first();

                if (!$schedule) break;

                $this->scheduleService->handle($schedule, true);
                break;
        }
    }
}
