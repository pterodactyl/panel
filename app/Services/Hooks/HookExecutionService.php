<?php

namespace Pterodactyl\Services\Hooks;


use Pterodactyl\Jobs\Hook\ExecuteHookActionJob;
use Pterodactyl\Models\Schedule;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Pterodactyl\Exceptions\ActionExecutionException;
use Pterodactyl\Exceptions\TriggerExecutionException;
use Pterodactyl\Models\Hook;
use Pterodactyl\Notifications\HookNotification;
use Pterodactyl\Notifications\MailTested;
use Pterodactyl\Services\Schedules\ProcessScheduleService;

class HookExecutionService
{
    /**
     * HookExecutionService constructor.
     */
    public function __construct() {}

    /**
     * Execute a hook
     *
     */
    public function handle(Hook $hook): void
    {
        ExecuteHookActionJob::dispatch($hook);
    }
}
