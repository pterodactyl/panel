<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Schedule extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'server_schedule';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'schedules';

    /**
     * Mass assignable attributes on this model.
     *
     * @var array
     */
    protected $fillable = [
        'server_id',
        'name',
        'cron_day_of_week',
        'cron_day_of_month',
        'cron_hour',
        'cron_minute',
        'is_active',
        'is_processing',
        'last_run_at',
        'next_run_at',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'server_id' => 'integer',
        'is_active' => 'boolean',
        'is_processing' => 'boolean',
    ];

    /**
     * Columns to mutate to a date.
     *
     * @var array
     */
    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        'last_run_at',
        'next_run_at',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'name' => null,
        'cron_day_of_week' => '*',
        'cron_day_of_month' => '*',
        'cron_hour' => '*',
        'cron_minute' => '*',
        'is_active' => true,
        'is_processing' => false,
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'server_id' => 'required',
        'cron_day_of_week' => 'required',
        'cron_day_of_month' => 'required',
        'cron_hour' => 'required',
        'cron_minute' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'server_id' => 'exists:servers,id',
        'name' => 'nullable|string|max:255',
        'cron_day_of_week' => 'string',
        'cron_day_of_month' => 'string',
        'cron_hour' => 'string',
        'cron_minute' => 'string',
        'is_active' => 'boolean',
        'is_processing' => 'boolean',
        'last_run_at' => 'nullable|date',
        'next_run_at' => 'nullable|date',
    ];

    /**
     * Return a hashid encoded string to represent the ID of the schedule.
     *
     * @return string
     */
    public function getHashidAttribute()
    {
        return app()->make('hashids')->encode($this->id);
    }

    /**
     * Return tasks belonging to a schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Return the server model that a schedule belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
