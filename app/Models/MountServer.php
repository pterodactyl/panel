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
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var null
     */
    protected $primaryKey = null;

    /**
     * @var bool
     */
    public $incrementing = false;
}
