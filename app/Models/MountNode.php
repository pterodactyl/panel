<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class MountNode extends Model
{
    protected $table = 'mount_node';

    protected $primaryKey;

    public $incrementing = false;
}
