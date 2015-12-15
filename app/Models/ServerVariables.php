<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class ServerVariables extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'server_variables';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

}
