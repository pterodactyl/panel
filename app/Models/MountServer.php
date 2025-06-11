<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class MountServer extends Model
{
    protected $table = 'mount_server';

    public $timestamps = false;

    protected $primaryKey = null;

    public $incrementing = false;
}
