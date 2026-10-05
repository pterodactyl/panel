<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\Carbon;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Traits\HasHashid;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $sequence_id
 * @property string $action
 * @property string $payload
 * @property int $time_offset
 * @property bool $is_queued
 * @property bool $continue_on_failure
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Schedule $schedule
 * @property Server $server
 */
#[Fillable([
    'schedule_id',
    'sequence_id',
    'action',
    'payload',
    'time_offset',
    'is_queued',
    'continue_on_failure',
])]
#[Touches(['schedule'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasHashid;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'schedule_task';

    /**
     * The default actions that can exist for a task in Pterodactyl.
     */
    public const string ACTION_POWER = 'power';

    public const string ACTION_COMMAND = 'command';

    public const string ACTION_BACKUP = 'backup';

    /**
     * The signals a power task may send.
     *
     * @var list<string>
     */
    public const array POWER_ACTIONS = ['start', 'stop', 'restart', 'kill'];

    /**
     * Default attributes when creating a new model.
     */
    protected $attributes = [
        'time_offset' => 0,
        'is_queued' => false,
        'continue_on_failure' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'schedule_id' => 'integer',
            'sequence_id' => 'integer',
            'time_offset' => 'integer',
            'is_queued' => 'boolean',
            'continue_on_failure' => 'boolean',
        ];
    }

    /**
     * The permission a user needs to run a task with the given action and payload, or
     * null when the action or power signal is not one a task can perform.
     */
    public static function permissionForAction(string $action, ?string $payload = null): ?Permissions
    {
        return match ($action) {
            self::ACTION_COMMAND => Permissions::ControlConsole,
            self::ACTION_BACKUP => Permissions::BackupCreate,
            self::ACTION_POWER => match (mb_trim((string) $payload)) {
                'start' => Permissions::ControlStart,
                'stop', 'kill' => Permissions::ControlStop,
                'restart' => Permissions::ControlRestart,
                default => null,
            },
            default => null,
        };
    }

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Return the schedule that a task belongs to.
     *
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * The server a task runs against is whatever its schedule belongs to, so this
     * resolves through the schedule with local keys.
     *
     * @return HasOneThrough<Server, Schedule, $this>
     */
    public function server(): HasOneThrough
    {
        return $this->hasOneThrough(Server::class, Schedule::class, 'id', 'id', 'schedule_id', 'server_id');
    }
}
