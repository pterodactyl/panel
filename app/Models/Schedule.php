<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Database\Factories\ScheduleFactory;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pterodactyl\Models\Traits\HasHashid;

/**
 * @property-read Server $server
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

    public const string RESOURCE_NAME = 'server_schedule';

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
     * @throws Exception
     */
    public function getNextRunDate(): CarbonImmutable
    {
        $formatted = sprintf('%s %s %s %s %s', $this->cron_minute, $this->cron_hour, $this->cron_day_of_month, $this->cron_month, $this->cron_day_of_week);

        return CarbonImmutable::instance((new CronExpression($formatted))->getNextRunDate());
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->chaperone('schedule');
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
