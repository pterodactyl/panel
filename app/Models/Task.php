<?php

namespace Pterodactyl\Models;

use Illuminate\Container\Container;
use Znck\Eloquent\Traits\BelongsToThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $sequence_id
 * @property string $action
 * @property string $payload
 * @property int $time_offset
 * @property bool $is_queued
 * @property bool $continue_on_failure
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $hashid
 * @property \Pterodactyl\Models\Schedule $schedule
 * @property \Pterodactyl\Models\Server $server
 */
class Task extends Model
{
    use BelongsToThrough;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'schedule_task';

    /**
     * The default actions that can exist for a task in Pterodactyl.
     */
    public const ACTION_POWER = 'power';
    public const ACTION_COMMAND = 'command';
    public const ACTION_BACKUP = 'backup';

    /**
     * The table associated with the model.
     */
    protected $table = 'tasks';

    /**
     * Relationships to be updated when this model is updated.
     */
    protected $touches = ['schedule'];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'schedule_id',
        'sequence_id',
        'action',
        'payload',
        'time_offset',
        'is_queued',
        'continue_on_failure',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'id' => 'integer',
        'schedule_id' => 'integer',
        'sequence_id' => 'integer',
        'time_offset' => 'integer',
        'is_queued' => 'boolean',
        'continue_on_failure' => 'boolean',
    ];

    /**
     * Default attributes when creating a new model.
     */
    protected $attributes = [
        'time_offset' => 0,
        'is_queued' => false,
        'continue_on_failure' => false,
    ];

    public static array $validationRules = [
        'schedule_id' => 'required|numeric|exists:schedules,id',
        'sequence_id' => 'required|numeric|min:1',
        'action' => 'required|string',
        'payload' => 'required_unless:action,backup|string',
        'time_offset' => 'required|numeric|between:0,900',
        'is_queued' => 'boolean',
        'continue_on_failure' => 'boolean',
    ];

    /**
     * {@inheritDoc}
     */
    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Return a hashid encoded string to represent the ID of the task.
     */
    public function getHashidAttribute(): string
    {
        return Container::getInstance()->make(HashidsInterface::class)->encode($this->id);
    }

    /**
     * Return the schedule that a task belongs to.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Return the server a task is assigned to, acts as a belongsToThrough.
     */
    public function server(): \Znck\Eloquent\Relations\BelongsToThrough
    {
        return $this->belongsToThrough(Server::class, Schedule::class);
    }
}
