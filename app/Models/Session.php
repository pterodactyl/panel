<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'sessions';

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'id' => 'string',
        'user_id' => 'integer',
    ];
}
