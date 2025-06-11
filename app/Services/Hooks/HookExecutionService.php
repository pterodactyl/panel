<?php

namespace Pterodactyl\Services\Hooks;


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

        $config = $action->config;

        switch ($action->type) {
            case 'run_schedule':
                $scheduleId = $config[0] ?? null;
                if (!$scheduleId) break;

                $schedule = Schedule::where('id', $scheduleId)
                    ->where('server_id', $hook->server_id)
                    ->first();

                if (!$schedule) {
                    throw new ActionExecutionException("Schedule ID is no longer valid", []);
                }

                $this->scheduleService->handle($schedule, true);
                break;
            case 'discord_webhook':
                $webhookUrl = $config[0] ?? null;
                $message = $config[1] ?? null;
                if (!$webhookUrl || !$message) {
                    throw new ActionExecutionException("Webhook URL or message is missing.", []);
                }
                try {
                    Http::post($webhookUrl, [
                        "content" => $message,
                    ]);
                } catch (RequestException $exception) {
                    throw new ActionExecutionException("Error sending request to discord webhook", []);
                }
                break;
            case 'send_email':
                $receiver = $config[0] ?? $hook->server?->user?->email;
                $subject = $config[1] ?? null;
                $message = $config[2] ?? null;
                if (!$message) throw new ActionExecutionException("Error sending email, missing message", []);
                Notification::route('mail', $receiver)
                    ->notify(new HookNotification($subject,$message,"Heads up!"));
        }
    }
}
