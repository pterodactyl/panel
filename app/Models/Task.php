<?php

namespace Pterodactyl\Models;

use Znck\Eloquent\Traits\BelongsToThrough;

class Task extends Validable
{
    use BelongsToThrough;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'schedule_task';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * Relationships to be updated when this model is updated.
     *
     * @var array
     */
    protected $touches = ['schedule'];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'schedule_id',
        'sequence_id',
        'action',
        'payload',
        'time_offset',
        'is_queued',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'schedule_id' => 'integer',
        'sequence_id' => 'integer',
        'time_offset' => 'integer',
        'is_queued' => 'boolean',
    ];

    /**
     * Default attributes when creating a new model.
     *
     * @var array
     */
    protected $attributes = [
        'is_queued' => false,
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'schedule_id' => 'required|numeric|exists:schedules,id',
        'sequence_id' => 'required|numeric|min:1',
        'action' => 'required|string',
        'payload' => 'required|string',
        'time_offset' => 'required|numeric|between:0,900',
        'is_queued' => 'boolean',
    ];

    /**
     * Return a hashid encoded string to represent the ID of the task.
     *
     * @return string
     */
    public function getHashidAttribute()
    {
        return app()->make('hashids')->encode($this->id);
    }

    /**
     * Return the schedule that a task belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Return the server a task is assigned to, acts as a belongsToThrough.
     *
     * @return \Znck\Eloquent\Relations\BelongsToThrough
     * @throws \Exception
     */
    public function server()
    {
        return $this->belongsToThrough(Server::class, Schedule::class);
    }
}
