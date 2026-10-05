<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Database\Factories\ScheduleFactory;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Pterodactyl\Models\Traits\HasHashid;

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
 * @property Carbon|null $last_run_at
 * @property Carbon|null $next_run_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Server $server
 * @property Collection<int, Task> $tasks
 */
#[Fillable([
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
])]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    use HasHashid;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'server_schedule';

    /**
     * Always return the tasks associated with this schedule.
     */
    protected $with = ['tasks'];

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'server_id' => 'integer',
            'is_active' => 'boolean',
            'is_processing' => 'boolean',
            'only_when_online' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Returns the schedule's execution crontab entry as a string.
     *
     * @throws Exception
     */
    public function getNextRunDate(): CarbonImmutable
    {
        $formatted = sprintf('%s %s %s %s %s', $this->cron_minute, $this->cron_hour, $this->cron_day_of_month, $this->cron_month, $this->cron_day_of_week);

        return CarbonImmutable::instance((new CronExpression($formatted))->getNextRunDate());
    }

    /**
     * Return tasks belonging to a schedule.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->chaperone('schedule');
    }

    /**
     * Return the server model that a schedule belongs to.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
