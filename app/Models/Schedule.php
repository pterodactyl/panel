<?php

namespace Pterodactyl\Models;

use Cron\CronExpression;
use Carbon\CarbonImmutable;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

/**
 * @property int $id
 * @property int $server_id
 * @property string $name
 * @property string $cron_day_of_week
 * @property string $cron_month
 * @property string $cron_day_of_month
 * @property string $cron_hour
 * @property string $cron_minute
 * @property bool $is_active
 * @property bool $is_processing
 * @property bool $only_when_online
 * @property \Carbon\Carbon|null $last_run_at
 * @property \Carbon\Carbon|null $next_run_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $hashid
 * @property Server $server
 * @property \Illuminate\Database\Eloquent\Collection<int, \Pterodactyl\Models\Task> $tasks
 */
class Schedule extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleFactory> */
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_schedule';

    /**
     * The table associated with the model.
     */
    protected $table = 'schedules';

    /**
     * Always return the tasks associated with this schedule.
     */
    protected $with = ['tasks'];

    /**
     * Mass assignable attributes on this model.
     */
    protected $fillable = [
        'server_id',
        'name',
        'cron_day_of_week',
        'cron_month',
        'cron_day_of_month',
        'cron_hour',
        'cron_minute',
        'is_active',
        'is_processing',
        'only_when_online',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'server_id' => 'integer',
        'is_active' => 'boolean',
        'is_processing' => 'boolean',
        'only_when_online' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    protected $attributes = [
        'name' => null,
        'cron_day_of_week' => '*',
        'cron_month' => '*',
        'cron_day_of_month' => '*',
        'cron_hour' => '*',
        'cron_minute' => '*',
        'is_active' => true,
        'is_processing' => false,
        'only_when_online' => false,
    ];

    public static array $validationRules = [
        'server_id' => 'required|exists:servers,id',
        'name' => 'required|string|max:191',
        'cron_day_of_week' => 'required|string',
        'cron_month' => 'required|string',
        'cron_day_of_month' => 'required|string',
        'cron_hour' => 'required|string',
        'cron_minute' => 'required|string',
        'is_active' => 'boolean',
        'is_processing' => 'boolean',
        'only_when_online' => 'boolean',
        'last_run_at' => 'nullable|date',
        'next_run_at' => 'nullable|date',
    ];

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Returns the schedule's execution crontab entry as a string.
     *
     * @throws \Exception
     */
    public function getNextRunDate(): CarbonImmutable
    {
        $formatted = sprintf('%s %s %s %s %s', $this->cron_minute, $this->cron_hour, $this->cron_day_of_month, $this->cron_month, $this->cron_day_of_week);

        return CarbonImmutable::instance((new CronExpression($formatted))->getNextRunDate());
    }

    /**
     * Return a hashid encoded string to represent the ID of the schedule.
     */
    public function getHashidAttribute(): string
    {
        return Container::getInstance()->make(HashidsInterface::class)->encode($this->id);
    }

    /**
     * Return tasks belonging to a schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Return the server model that a schedule belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Pterodactyl\Models\Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
