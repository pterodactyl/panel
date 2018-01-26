<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Znck\Eloquent\Traits\BelongsToThrough;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Task extends Model implements CleansAttributes, ValidableContract
{
    use BelongsToThrough, Eloquence, Validable;

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
    protected static $applicationRules = [
        'schedule_id' => 'required',
        'sequence_id' => 'required',
        'action' => 'required',
        'payload' => 'required',
        'time_offset' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'schedule_id' => 'numeric|exists:schedules,id',
        'sequence_id' => 'numeric|min:1',
        'action' => 'string',
        'payload' => 'string',
        'time_offset' => 'numeric|between:0,900',
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
