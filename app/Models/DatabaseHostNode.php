<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $node_id
 * @property int $database_host_id
 */
class DatabaseHostNode extends Model
{
    /**
     * @var string
     */
    protected $table = 'database_host_node';

    /**
     * @var null
     */
    protected $primaryKey = null;

    /**
     * @var bool
     */
    public $incrementing = false;
}
