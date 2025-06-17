<?php

namespace Pterodactyl\Jobs\Hook;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Pterodactyl\Exceptions\ActionExecutionException;
use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Notifications\HookNotification;
use Pterodactyl\Services\Schedules\ProcessScheduleService;

class ExecuteHookActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(public Hook $hook){}

    /**
     * Execute the job.
     */
    public function handle(ProcessScheduleService $scheduleService): void
    {
        $hookId = $this->hook->id;
        $action = $this->hook->action;
        if (!$action) {
            return;
        }

        $ratelimitKey = "hook:{$hookId}:{$action->type}:rate_limit";

        if (in_array($action->type, ['discord_webhook', 'send_email']) && Cache::has($ratelimitKey)) {
            Log::info("Rate limit triggered for hook #{$hookId} ({$action->type})");
            return;
        }

        if (in_array($action->type, ['discord_webhook', 'send_email'])) {
            Cache::put($ratelimitKey, true, now()->addSeconds(30));
        }

        $config = $action->config;

        switch ($action->type) {
            case 'run_schedule':
                $scheduleId = $config[0] ?? null;
                if (!$scheduleId) break;

                $schedule = Schedule::where('id', $scheduleId)
                    ->where('server_id', $this->hook->server_id)
                    ->first();

                if (!$schedule) {
                    throw new ActionExecutionException("Schedule ID is no longer valid", []);
                }
                $scheduleService->handle($schedule, true);
                break;
            case 'discord_webhook':
                $webhookUrl = $config[0] ?? null;
                $message = $config[1] ?? null;
                if (!$webhookUrl || !$message) {
                    throw new ActionExecutionException("Webhook URL or message is missing.", []);
                }
                if (!preg_match('/^https:\/\/(discord\.com|discordapp\.com)\/api\/webhooks\/\d+\/[A-Za-z0-9_-]+$/', $webhookUrl)) {
                    throw new ActionExecutionException("The Discord webhook URL is invalid.", []);
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
                $receiver = $config[0] ?? $this->hook->server?->user?->email;
                $subject = $config[1] ?? null;
                $message = $config[2] ?? null;
                if (!$message) throw new ActionExecutionException("Error sending email, missing message", []);
                Notification::route('mail', $receiver)
                    ->notify(new HookNotification($subject,$message,"Heads up!"));
        }
    }

    public function failed(\Exception $exception)
    {
        Log::error("Hook ID {$this->hook->id} failed" . $exception->getMessage());
    }
}
