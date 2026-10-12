<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Activity;

use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Pterodactyl\Contracts\Activity\IngestsActivityLogs;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\ActivityLogSubject;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

/**
 * @phpstan-type ActivityEvent array{user: string|null, server: string, event: string, metadata: array<mixed>, ip: string|null, timestamp: string}
 */
final class IngestActivityLogs implements IngestsActivityLogs
{
    /**
     * The activity Wings reports, under the field names of its protocol: `data` lists the
     * events, each naming the server it happened on, the event, its metadata and when it
     * happened, and optionally the user who caused it and their IP address.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.*' => ['array'],
            'data.*.user' => ['sometimes', 'nullable', 'uuid'],
            'data.*.server' => ['required', 'uuid'],
            'data.*.event' => ['required', 'string'],
            'data.*.metadata' => ['present', 'nullable', 'array'],
            'data.*.ip' => ['sometimes', 'nullable', 'ip'],
            'data.*.timestamp' => ['required', 'string'],
        ];
    }

    /**
     * Validates reported activity and returns the events it lists. Metadata given as null
     * is empty.
     *
     * @param  array<string, mixed>  $input
     * @return list<ActivityEvent>
     *
     * @throws ValidationException
     */
    public static function events(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();

        $events = [];
        foreach (array_keys($input->array('data')) as $key) {
            $event = 'data.'.$key.'.';

            $events[] = [
                'user' => $input->filled($event.'user') ? $input->string($event.'user')->toString() : null,
                'server' => $input->string($event.'server')->toString(),
                'event' => $input->string($event.'event')->toString(),
                'metadata' => $input->array($event.'metadata'),
                'ip' => $input->filled($event.'ip') ? $input->string($event.'ip')->toString() : null,
                'timestamp' => $input->string($event.'timestamp')->toString(),
            ];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function ingest(Node $node, array $input): void
    {
        $events = self::events($input);

        $tz = now()->getTimezone();
        $servers = $node->servers()->whereIn('uuid', array_column($events, 'server'))->get()->keyBy('uuid');
        $users = User::query()->whereIn('uuid', array_filter(array_column($events, 'user')))->get()->keyBy('uuid');
        $logs = [];

        foreach ($events as $datum) {
            $server = $servers->get($datum['server']);
            if ($server === null || ! Str::startsWith($datum['event'], 'server:')) {
                continue;
            }

            try {
                $when = Date::createFromFormat(DateTimeInterface::RFC3339, preg_replace('/(\.\d+)Z$/', 'Z', $datum['timestamp']) ?? $datum['timestamp'], 'UTC')
                    ?? throw new InvalidArgumentException('The activity timestamp could not be parsed.');
            } catch (Exception $exception) {
                Log::warning($exception->getMessage(), ['exception' => $exception, 'timestamp' => $datum['timestamp']]);
                $when = now();
                $datum['metadata'] = array_merge($datum['metadata'], ['original_timestamp' => $datum['timestamp']]);
            }

            $log = [
                'ip' => empty($datum['ip']) ? '127.0.0.1' : $datum['ip'],
                'event' => $datum['event'],
                'properties' => json_encode($datum['metadata']),
                'timestamp' => $when->setTimezone($tz),
            ];

            if ($user = $users->get($datum['user'] ?? null)) {
                $log['actor_id'] = $user->id;
                $log['actor_type'] = $user->getMorphClass();
            }

            $logs[$datum['server']][] = $log;
        }

        foreach ($logs as $key => $data) {
            $server = $servers->get($key);
            throw_unless($server instanceof Server, InvalidArgumentException::class, "Server [$key] was not found while processing activity.");
            $batch = [];

            foreach ($data as $datum) {
                $id = ActivityLog::query()->insertGetId($datum);
                $batch[] = [
                    'activity_log_id' => $id,
                    'subject_id' => $server->id,
                    'subject_type' => $server->getMorphClass(),
                ];
            }

            ActivityLogSubject::query()->insert($batch);
        }
    }
}
