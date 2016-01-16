<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class APIPermission extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_permissions';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];


}
