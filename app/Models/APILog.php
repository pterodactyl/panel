<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class APILog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'api_logs';

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = [];

    /**
     * Fields that are not mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'authorized' => 'boolean',
    ];
}
