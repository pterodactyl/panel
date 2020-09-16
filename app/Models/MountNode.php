<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class MountNode extends Model
{
    /**
     * @var string
     */
    protected $table = 'mount_node';

    /**
     * @var null
     */
    protected $primaryKey = null;

    /**
     * @var bool
     */
    public $incrementing = false;
}
