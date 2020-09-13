<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class MountServer extends Model
{
    /**
     * @var string
     */
    protected $table = 'mount_server';

    /**
     * @var null
     */
    protected $primaryKey = null;

    /**
     * @var bool
     */
    public $incrementing = false;
}
