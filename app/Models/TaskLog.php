<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class TaskLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'tasks_log';

    /**
     * Fields that are not mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'id' => 'integer',
        'task_id' => 'integer',
        'run_status' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = ['run_time', 'created_at', 'updated_at'];
}
