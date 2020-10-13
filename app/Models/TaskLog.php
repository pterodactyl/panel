<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class TaskLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks_log';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'task_id' => 'integer',
        'run_status' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['run_time', 'created_at', 'updated_at'];
}
