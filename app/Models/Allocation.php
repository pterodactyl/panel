<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class Allocation extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'allocations';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

}
